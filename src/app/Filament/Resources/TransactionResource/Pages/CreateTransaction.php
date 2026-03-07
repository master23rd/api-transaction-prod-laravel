<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\TransactionDetailOption;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = TransactionResource::class;

    protected function getSteps(): array
    {
        return [
            Step::make('Order Information')
                ->icon('heroicon-o-user')
                ->description('Select customer and cafe')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Customer')
                        ->options(
                            User::role('customer')->pluck('name', 'id')
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('cafe_id')
                        ->label('Cafe')
                        ->relationship('cafe', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->columns(2),

            Step::make('Products')
                ->icon('heroicon-o-shopping-bag')
                ->description('Add products to order')
                ->schema([
                    Forms\Components\Repeater::make('details')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Product')
                                ->options(Product::query()->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('price', $product->price);
                                            $quantity = $get('quantity') ?: 1;
                                            $set('total_amount', $product->price * $quantity);
                                            // Reset options
                                            $set('size_option', null);
                                            $set('dairy_option', null);
                                            $set('sweetness_option', null);
                                            $set('ice_option', null);
                                        }
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\Group::make([
                                Forms\Components\Select::make('size_option')
                                    ->label('Size')
                                    ->options(fn (Get $get) => $get('product_id')
                                        ? ProductOption::where('product_id', $get('product_id'))
                                            ->where('type', 'size')
                                            ->get()
                                            ->mapWithKeys(fn ($opt) => [$opt->id => $opt->name . ($opt->price > 0 ? ' (+Rp ' . number_format($opt->price, 0, ',', '.') . ')' : '')])
                                        : []
                                    )
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculatePrice($set, $get)),
                                Forms\Components\Select::make('dairy_option')
                                    ->label('Dairy')
                                    ->options(fn (Get $get) => $get('product_id')
                                        ? ProductOption::where('product_id', $get('product_id'))
                                            ->where('type', 'dairy')
                                            ->get()
                                            ->mapWithKeys(fn ($opt) => [$opt->id => $opt->name . ($opt->price > 0 ? ' (+Rp ' . number_format($opt->price, 0, ',', '.') . ')' : '')])
                                        : []
                                    )
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculatePrice($set, $get)),
                                Forms\Components\Select::make('sweetness_option')
                                    ->label('Sweetness')
                                    ->options(fn (Get $get) => $get('product_id')
                                        ? ProductOption::where('product_id', $get('product_id'))
                                            ->where('type', 'sweetness')
                                            ->pluck('name', 'id')
                                        : []
                                    ),
                                Forms\Components\Select::make('ice_option')
                                    ->label('Ice')
                                    ->options(fn (Get $get) => $get('product_id')
                                        ? ProductOption::where('product_id', $get('product_id'))
                                            ->where('type', 'ice')
                                            ->pluck('name', 'id')
                                        : []
                                    ),
                            ])
                                ->columns(4)
                                ->columnSpanFull()
                                ->visible(fn (Get $get) => $get('product_id') &&
                                    !in_array(Product::find($get('product_id'))?->category?->slug, ['snack', 'merchandise'])
                                ),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculatePrice($set, $get)),
                            Forms\Components\TextInput::make('price')
                                ->label('Unit Price')
                                ->numeric()
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('total_amount')
                                ->label('Subtotal')
                                ->numeric()
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->addActionLabel('Add Product')
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string =>
                            $state['product_id'] ? Product::find($state['product_id'])?->name : null
                        ),
                ]),

            Step::make('Payment')
                ->icon('heroicon-o-credit-card')
                ->description('Payment details')
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
                        ->default('pending')
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('order_status')
                        ->options([
                            'pending' => 'Pending',
                            'preparing' => 'Preparing',
                            'finished' => 'Finished',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('discount')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),
                    Forms\Components\TextInput::make('tax_percentage_amount')
                        ->label('Tax Percentage')
                        ->numeric()
                        ->suffix('%')
                        ->default(11),
                    Forms\Components\TextInput::make('service_fee_amount')
                        ->label('Service Fee')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(2000),
                    Forms\Components\FileUpload::make('proof_of_payment')
                        ->image()
                        ->directory('transactions/proofs')
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ];
    }

    protected static function recalculatePrice(Set $set, Get $get): void
    {
        $product = Product::find($get('product_id'));
        if (!$product) return;

        $basePrice = $product->price;
        $optionPrice = 0;

        // Add option prices (only size and dairy have additional prices)
        foreach (['size_option', 'dairy_option'] as $optionField) {
            $optionId = $get($optionField);
            if ($optionId) {
                $option = ProductOption::find($optionId);
                if ($option) {
                    $optionPrice += $option->price;
                }
            }
        }

        $totalPrice = $basePrice + $optionPrice;
        $quantity = $get('quantity') ?: 1;

        $set('price', $totalPrice);
        $set('total_amount', $totalPrice * $quantity);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculate totals from details
        $details = $data['details'] ?? [];
        $subtotal = 0;
        $totalItems = 0;

        foreach ($details as $detail) {
            $subtotal += $detail['total_amount'] ?? 0;
            $totalItems += $detail['quantity'] ?? 0;
        }

        // Calculate tax
        $taxPercentage = $data['tax_percentage_amount'] ?? 11;
        $taxAmount = (int) ($subtotal * $taxPercentage / 100);

        // Get service fee and discount
        $serviceFee = $data['service_fee_amount'] ?? 2000;
        $discount = $data['discount'] ?? 0;

        // Calculate grand total
        $grandTotal = $subtotal + $taxAmount + $serviceFee - $discount;

        // Set calculated values
        $data['total_items'] = $totalItems;
        $data['total_tax_amount'] = $taxAmount;
        $data['grand_total_amount'] = $grandTotal;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Save selected options for each detail
        $transaction = $this->record;
        $formData = $this->form->getState();

        foreach ($formData['details'] ?? [] as $index => $detailData) {
            $detail = $transaction->details[$index] ?? null;
            if (!$detail) continue;

            foreach (['size_option', 'dairy_option', 'sweetness_option', 'ice_option'] as $optionField) {
                if (!empty($detailData[$optionField])) {
                    $option = ProductOption::find($detailData[$optionField]);
                    if ($option) {
                        TransactionDetailOption::create([
                            'transaction_detail_id' => $detail->id,
                            'product_option_id' => $option->id,
                            'price' => $option->price,
                        ]);
                    }
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
