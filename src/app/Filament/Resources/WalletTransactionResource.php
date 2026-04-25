<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    // protected static ?string $navigationLabel = 'Wallet Transactions';
    protected static ?string $navigationLabel = 'Transaksi Tabungan';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Information')
                    ->schema([
                        Forms\Components\Select::make('wallet_id')
                            ->relationship('wallet', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name . ' - ' . $record->user->email)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'topup' => 'Top Up',
                                'payment' => 'Payment',
                                'refund' => 'Refund',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (Added to Wallet)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Transfer Amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('service_fee')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        // Forms\Components\TextInput::make('unique_code')
                        //     ->numeric()
                        //     ->minValue(1)
                        //     ->maxValue(200),
                        Forms\Components\FileUpload::make('proof_of_payment')
                            ->image()
                            ->directory('wallet-proofs'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallet.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'topup' => 'success',
                        'payment' => 'warning',
                        'refund' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'topup' => 'Top Up',
                        'payment' => 'Payment',
                        'refund' => 'Refund',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('wallet.account_number')
                    ->label('No Rekening')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Ref Code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Top Up Amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Transfer Amount')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                // Tables\Columns\TextColumn::make('unique_code')
                //     ->label('Unique Code')
                //     ->sortable()
                //     ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\ImageColumn::make('proof_of_payment')
                    ->label('Proof')
                    ->disk('public')
                    ->circular()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'topup' => 'Top Up',
                        'payment' => 'Payment',
                        'refund' => 'Refund',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (WalletTransaction $record): bool => $record->status === 'pending' && $record->type === 'topup')
                    ->requiresConfirmation()
                    ->action(function (WalletTransaction $record): void {
                        $record->update(['status' => 'approved']);

                        // Update wallet balance for topup - use amount (pure top-up amount)
                        $wallet = $record->wallet;
                        $wallet->update(['balance' => $wallet->balance + $record->amount]);

                        Notification::make()
                            ->title('Transaction approved')
                            ->body('Wallet balance updated: +Rp ' . number_format($record->amount, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (WalletTransaction $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (WalletTransaction $record): void {
                        $record->update(['status' => 'rejected']);

                        Notification::make()
                            ->title('Transaction rejected')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
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
                        Infolists\Components\TextEntry::make('wallet.user.name')
                            ->label('Customer Name'),
                        Infolists\Components\TextEntry::make('wallet.user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('wallet.account_number')
                            ->label('No Rekening')
                            ->icon('heroicon-o-credit-card'),
                    ])->columns(3),

                Infolists\Components\Section::make('Transaction Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference_code')
                            ->label('Reference Code')
                            ->badge()
                            ->copyable(),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'topup' => 'success',
                                'payment' => 'warning',
                                'refund' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Top Up Amount')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Transfer Amount')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->prose()
                            ->columnSpanFull(),
                        // Infolists\Components\TextEntry::make('service_fee')
                        //     ->money('IDR'),
                        // Infolists\Components\TextEntry::make('unique_code'),
                    ])->columns(3),

                Infolists\Components\Section::make('Proof of Payment')
                    ->schema([
                        Infolists\Components\ImageEntry::make('proof_of_payment')
                            ->disk('public')
                            ->height(300)
                            ->extraImgAttributes(['class' => 'rounded-lg']),
                    ])
                    ->visible(fn (WalletTransaction $record): bool => $record->proof_of_payment !== null),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }
}
