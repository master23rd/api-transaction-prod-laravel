<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Filament\Resources\WalletResource\RelationManagers;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Tabungan';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Wallet Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->options(
                                User::role('customer')
                                    ->whereDoesntHave('wallet')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                        Forms\Components\TextInput::make('balance')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('user.photo')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn (Wallet $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name) . '&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn (Wallet $record): string => $record->balance > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('wallet_transactions_count')
                    ->label('Transactions')
                    ->counts('walletTransactions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_balance')
                    ->label('Has Balance')
                    ->query(fn ($query) => $query->where('balance', '>', 0)),
                Tables\Filters\Filter::make('zero_balance')
                    ->label('Zero Balance')
                    ->query(fn ($query) => $query->where('balance', '=', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('topUp')
                    ->label('Top Up')
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
                                'success' => 'Success',
                            ])
                            ->default('success')
                            ->required(),
                        Forms\Components\FileUpload::make('proof_of_payment')
                            ->image()
                            ->directory('wallets/proofs'),
                    ])
                    ->action(function (Wallet $record, array $data): void {
                        // Create wallet transaction (unique code max 200)
                        $uniqueCode = rand(1, 200);

                        WalletTransaction::create([
                            'wallet_id' => $record->id,
                            'amount' => $data['amount'],
                            'total_amount' => $data['amount'],
                            'type' => 'topup',
                            'status' => $data['status'],
                            'proof_of_payment' => $data['proof_of_payment'] ?? null,
                            'service_fee' => 0,
                            'unique_code' => $uniqueCode,
                        ]);

                        // Update balance if success
                        if ($data['status'] === 'success') {
                            $record->update([
                                'balance' => $record->balance + $data['amount'],
                            ]);
                        }

                        Notification::make()
                            ->title('Top up successful')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('deduct')
                    ->label('Deduct')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Deduct Amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1000),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (Wallet $record, array $data): void {
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
                            'status' => 'approved',
                            'service_fee' => 0,
                        ]);

                        // Update balance
                        $record->update([
                            'balance' => $record->balance - $data['amount'],
                        ]);

                        Notification::make()
                            ->title('Balance deducted')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Wallet $record): bool => $record->balance > 0),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\ImageEntry::make('user.photo')
                            ->label('')
                            ->circular()
                            ->defaultImageUrl(fn (Wallet $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name) . '&color=7F9CF5&background=EBF4FF&size=200'),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('user.name')
                                ->label('Customer Name')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight('bold'),
                            Infolists\Components\TextEntry::make('user.email')
                                ->label('Email')
                                ->icon('heroicon-o-envelope'),
                            Infolists\Components\TextEntry::make('user.phone')
                                ->label('Phone')
                                ->icon('heroicon-o-phone')
                                ->default('-'),
                            Infolists\Components\TextEntry::make('branch.name')
                                ->label('Branch')
                                ->icon('heroicon-o-building-storefront'),
                        ]),
                    ])->columns(2),

                Infolists\Components\Section::make('Wallet Balance')
                    ->schema([
                        Infolists\Components\TextEntry::make('balance')
                            ->money('IDR')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color(fn (Wallet $record): string => $record->balance > 0 ? 'success' : 'gray'),
                        Infolists\Components\TextEntry::make('wallet_transactions_count')
                            ->label('Total Transactions')
                            ->getStateUsing(fn (Wallet $record): int => $record->walletTransactions()->count()),
                        Infolists\Components\TextEntry::make('total_top_up')
                            ->label('Total Top Up')
                            ->getStateUsing(fn (Wallet $record): string => 'Rp ' . number_format(
                                $record->walletTransactions()->where('type', 'top_up')->where('status', 'success')->sum('total_amount'),
                                0, ',', '.'
                            )),
                        Infolists\Components\TextEntry::make('total_spent')
                            ->label('Total Spent')
                            ->getStateUsing(fn (Wallet $record): string => 'Rp ' . number_format(
                                $record->walletTransactions()->where('type', 'payment')->where('status', 'success')->sum('total_amount'),
                                0, ',', '.'
                            )),
                    ])->columns(4),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->icon('heroicon-o-calendar'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->icon('heroicon-o-clock'),
                    ])->columns(2)->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WalletTransactionsRelationManager::class,
            // RelationManagers\BranchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'view' => Pages\ViewWallet::route('/{record}'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
