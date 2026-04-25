<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use App\Models\WalletTransaction;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWallet extends ViewRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('topUp')
                ->label('Setor')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Top Up Amount')
                        ->required()
                        ->numeric()
                        ->prefix('Rp')
                        ->minValue(10000)
                        ->default(50000),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                        ])
                        ->default('pending')
                        ->required(),
                    Forms\Components\FileUpload::make('proof_of_payment')
                        ->image()
                        ->directory('wallets/proofs'),
                ])
                ->action(function (array $data): void {
                    $record = $this->record;
                    // $uniqueCode = rand(100, 999);
                    
                    WalletTransaction::create([
                        'wallet_id' => $record->id,
                        'amount' => $data['amount'],
                        'total_amount' => $data['amount'],
                        'type' => 'topup',
                        'status' => $data['status'],
                        'proof_of_payment' => $data['proof_of_payment'] ?? null,
                        'service_fee' => 0,
                        // 'unique_code' => $uniqueCode,
                        // 'branch_id' => $record->branch_id,
                    ]);

                    if ($data['status'] === 'approved') {
                        $record->update([
                            'balance' => $record->balance + $data['amount'],
                        ]);
                    }

                    Notification::make()
                        ->title('Top up successful')
                        ->success()
                        ->send();
                }),
                Actions\Action::make('deduct')
                    ->label('Tarik')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Deduct Amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1000),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\FileUpload::make('proof_of_payment')
                            ->image()
                            ->directory('wallets/proofs'),
                        // Forms\Components\Textarea::make('reason')
                        //     ->label('Reason')
                        //     ->required(),
                    ])
                    ->action(function (array $data): void {
                        $record = $this->record;

                        if ($data['amount'] > $record->balance) {
                            Notification::make()
                                ->title('Insufficient balance')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Create wallet transaction
                        WalletTransaction::create([
                            'wallet_id' => $record->id,
                            'amount' => $data['amount'],
                            'total_amount' => $data['amount'],
                            'type' => 'payment',
                            'status' => $data['status'],
                            'service_fee' => 0,
                            'branch_id' => $record->branch_id,
                        ]);

                        // Update balance
                        if ($data['status'] === 'approved') {
                            $record->update([
                                'balance' => $record->balance - $data['amount'],
                            ]);
                        }

                        Notification::make()
                            ->title('Balance deducted')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->balance > 0),
                Actions\EditAction::make(),
        ];
    }
}
