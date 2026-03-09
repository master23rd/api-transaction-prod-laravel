<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'Order';

    protected static ?string $pluralModelLabel = 'Orders';

    public static function form(Form $form): Form
    {
        // This form is used by EditTransaction page (overridden there)
        // CreateTransaction uses wizard steps defined in getSteps()
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('cafe_id')
                            ->label('Koperasi')
                            ->relationship('cafe', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'wallet' => 'Wallet',
                                'bank_transfer' => 'Bank Transfer',
                                'qris' => 'QRIS',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('order_status')
                            ->options([
                                'pending' => 'Pending',
                                'preparing' => 'Preparing',
                                'finished' => 'Finished',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->native(false),
                    ])->columns(3),

                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\TextInput::make('discount')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('tax_percentage_amount')
                            ->label('Tax Percentage')
                            ->numeric()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('service_fee_amount')
                            ->label('Service Fee')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('total_tax_amount')
                            ->label('Tax Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_items')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('grand_total_amount')
                            ->label('Grand Total')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Proof of Payment')
                    ->schema([
                        Forms\Components\FileUpload::make('proof_of_payment')
                            ->image()
                            ->directory('transactions/proofs'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => '#' . str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cafe.name')
                    ->label('Koperasi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Items')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'gray',
                        'wallet' => 'info',
                        'bank_transfer' => 'warning',
                        'qris' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'bank_transfer' => 'Bank Transfer',
                        'qris' => 'QRIS',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('order_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'preparing' => 'info',
                        'finished' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('order_status')
                    ->options([
                        'pending' => 'Pending',
                        'preparing' => 'Preparing',
                        'finished' => 'Finished',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('cafe')
                    ->relationship('cafe', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'wallet' => 'Wallet',
                        'bank_transfer' => 'Bank Transfer',
                        'qris' => 'QRIS',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_preparing')
                    ->label('Mark as Preparing')
                    ->icon('heroicon-o-fire')
                    ->color('info')
                    ->visible(fn (Transaction $record): bool => $record->order_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Order as Preparing')
                    ->modalDescription('Are you sure you want to mark this order as preparing?')
                    ->action(function (Transaction $record): void {
                        $record->update(['order_status' => 'preparing']);

                        Notification::make()
                            ->title('Order marked as preparing')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('mark_finished')
                    ->label('Mark as Finished')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Transaction $record): bool => $record->order_status === 'preparing')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Order as Finished')
                    ->modalDescription('Are you sure you want to mark this order as finished?')
                    ->action(function (Transaction $record): void {
                        $record->update(['order_status' => 'finished']);

                        Notification::make()
                            ->title('Order marked as finished')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('mark_cancelled')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Transaction $record): bool => in_array($record->order_status, ['pending', 'preparing']))
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Order')
                    ->modalDescription('Are you sure you want to cancel this order? The amount will be refunded to the customer\'s wallet.')
                    ->action(function (Transaction $record): void {
                        // Update order status
                        $record->update([
                            'order_status' => 'cancelled',
                            'payment_status' => $record->payment_status === 'paid' ? 'refunded' : 'cancelled',
                        ]);

                        // Refund to wallet if payment was made
                        if ($record->payment_method === 'wallet' && $record->payment_status === 'refunded') {
                            $wallet = $record->user->wallet;

                            if ($wallet) {
                                // Restore wallet balance
                                $wallet->update(['balance' => $wallet->balance + $record->grand_total_amount]);

                                // Create refund wallet transaction
                                WalletTransaction::create([
                                    'wallet_id' => $wallet->id,
                                    'transaction_id' => $record->id,
                                    'amount' => $record->grand_total_amount,
                                    'total_amount' => $record->grand_total_amount,
                                    'type' => 'refund',
                                    'status' => 'approved',
                                    'service_fee' => 0,
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Order cancelled')
                            ->body('Amount refunded: Rp ' . number_format($record->grand_total_amount, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),
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
                Infolists\Components\Section::make('Order Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Order Number')
                            ->formatStateUsing(fn (string $state): string => '#' . str_pad($state, 5, '0', STR_PAD_LEFT))
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Order Date')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Customer')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('cafe.name')
                            ->label('Koperasi')
                            ->icon('heroicon-o-building-storefront'),
                    ])->columns(4),

                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'paid' => 'success',
                                'failed' => 'danger',
                                'refunded' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('order_status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'preparing' => 'info',
                                'finished' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        Infolists\Components\TextEntry::make('payment_method')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'bank_transfer' => 'Bank Transfer',
                                'qris' => 'QRIS',
                                default => ucfirst($state),
                            }),
                    ])->columns(3),

                Infolists\Components\Section::make('Order Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Qty'),
                                Infolists\Components\TextEntry::make('price')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Subtotal')
                                    ->money('IDR'),
                            ])
                            ->columns(4),
                    ]),

                Infolists\Components\Section::make('Payment Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_items')
                            ->label('Total Items'),
                        Infolists\Components\TextEntry::make('tax_percentage_amount')
                            ->label('Tax')
                            ->suffix('%'),
                        Infolists\Components\TextEntry::make('total_tax_amount')
                            ->label('Tax Amount')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('service_fee_amount')
                            ->label('Service Fee')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('discount')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('grand_total_amount')
                            ->label('Grand Total')
                            ->money('IDR')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                    ])->columns(3),

                Infolists\Components\Section::make('Proof of Payment')
                    ->schema([
                        Infolists\Components\ImageEntry::make('proof_of_payment')
                            ->label('')
                            ->height(300),
                    ])
                    ->visible(fn (Transaction $record): bool => $record->proof_of_payment !== null)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
