<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
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
                            ->label('Cafe')
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
                            ->prefix('Rp')
                            ->default(0),
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
}
