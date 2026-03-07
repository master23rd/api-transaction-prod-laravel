<?php

namespace App\Filament\Resources\TransactionResource\RelationManagers;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\TransactionDetailOption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $title = 'Order Items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product')
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
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                $price = $get('price') ?: 0;
                                $quantity = $state ?: 1;
                                $set('total_amount', $price * $quantity);
                            }),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(4),

                Forms\Components\Section::make('Options')
                    ->schema([
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
                    ->visible(fn (Get $get) => $get('product_id') &&
                        Product::find($get('product_id'))?->category?->slug !== 'snack' &&
                        Product::find($get('product_id'))?->category?->slug !== 'merchandise'
                    ),
            ]);
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\ImageColumn::make('product.thumbnail')
                    ->label('Image')
                    ->circular(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->options->map(fn ($opt) =>
                        $opt->productOption->name
                    )->join(', ') ?: null),
                Tables\Columns\TextColumn::make('product.category.name')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Coffee' => 'warning',
                        'Non Coffee' => 'info',
                        'Tea' => 'success',
                        'Snack' => 'danger',
                        'Merchandise' => 'gray',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('quantity')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Item')
                    ->after(function ($record, array $data) {
                        // Save selected options
                        foreach (['size_option', 'dairy_option', 'sweetness_option', 'ice_option'] as $optionField) {
                            if (!empty($data[$optionField])) {
                                $option = ProductOption::find($data[$optionField]);
                                if ($option) {
                                    TransactionDetailOption::create([
                                        'transaction_detail_id' => $record->id,
                                        'product_option_id' => $option->id,
                                        'price' => $option->price,
                                    ]);
                                }
                            }
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(fn ($record) => view('filament.transaction-detail-options', ['record' => $record])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
