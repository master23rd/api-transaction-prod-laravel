<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransactions';

    protected static ?string $title = 'Transaction History';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'top_up' => 'Top Up',
                        'payment' => 'Payment',
                        'refund' => 'Refund',
                    ])
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('service_fee')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                Forms\Components\TextInput::make('unique_code')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(200)
                    ->helperText('Unique code must be between 1-200'),
                Forms\Components\FileUpload::make('proof_of_payment')
                    ->image()
                    ->directory('wallets/proofs'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'top_up' => 'success',
                        'payment' => 'warning',
                        'refund' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'top_up' => 'Top Up',
                        'payment' => 'Payment',
                        'refund' => 'Refund',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn ($record): string => $record->type === 'top_up' || $record->type === 'refund' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($record): string =>
                        ($record->type === 'top_up' || $record->type === 'refund' ? '+' : '-') .
                        'Rp ' . number_format($record->total_amount, 0, ',', '.')
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('service_fee')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unique_code')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('transaction.id')
                    ->label('Order #')
                    ->formatStateUsing(fn (?string $state): string => $state ? '#' . str_pad($state, 5, '0', STR_PAD_LEFT) : '-')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'top_up' => 'Top Up',
                        'payment' => 'Payment',
                        'refund' => 'Refund',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Transaction'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->action(function ($record): void {
                        $record->update(['status' => 'success']);

                        // Update wallet balance
                        $wallet = $record->wallet;
                        if ($record->type === 'top_up' || $record->type === 'refund') {
                            $wallet->update(['balance' => $wallet->balance + $record->total_amount]);
                        } elseif ($record->type === 'payment') {
                            $wallet->update(['balance' => $wallet->balance - $record->total_amount]);
                        }
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'failed']))
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
