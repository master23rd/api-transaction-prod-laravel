<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\Select::make('store_id')
                            ->label('Store')
                            ->relationship('store', 'name') // 
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Image')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail')
                            ->image()
                            ->directory('products')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\Textarea::make('about')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull(),
                        // Forms\Components\TextInput::make('service_time')
                        //     ->label('Service Time (minutes)')
                        //     ->numeric()
                        //     ->suffix('min')
                        //     ->minValue(1)
                        //     ->default(5),
                        Forms\Components\TextInput::make('rate')
                            ->label('Rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->default(0)
                            ->suffix('/ 5'),
                    ])->columns(2),

                Forms\Components\Section::make('Visibility')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Product')
                            ->helperText('Featured products will be displayed in the "Featured For You" section')
                            ->default(false),
                        
                        Forms\Components\TextInput::make('count_click')
                            ->label('Jumlah Transaksi (*berdasrkan transaksi whatsapp)')
                            ->numeric()
                            ->default(0)
                            ->disabled() // ❗ tidak bisa diedit
                            ->dehydrated(false) // ❗ tidak ikut save
                            ->visible(fn ($operation) => $operation !== 'create'), // hanya tampil saat edit

                    ])->columns(2),
                
                // Forms\Components\Select::make('branch_id')
                //     ->label('Branch')
                //     ->options(\App\Models\Branch::pluck('name', 'id'))
                //     ->reactive()
                //     ->afterStateUpdated(fn ($state, $set) => $set('store_id', null)),
                
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Coffee' => 'warning',
                        'Non Coffee' => 'info',
                        'Tea' => 'success',
                        'Snack' => 'danger',
                        'Merchandise' => 'gray',
                        default => 'primary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('count_click')
                    ->label('Jumlah Transaksi')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Rating')
                    ->formatStateUsing(fn (string $state): string => $state . ' ⭐')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),
                // Tables\Columns\TextColumn::make('service_time')
                //     ->label('Service')
                //     ->suffix(' min')
                //     ->sortable()
                //     ->toggleable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All Products')
                    ->trueLabel('Featured Only')
                    ->falseLabel('Non-Featured Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
                Infolists\Components\Section::make('Basic Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('slug')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('category.name')
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
                        Infolists\Components\TextEntry::make('price')
                            ->money('IDR'),
                        Infolists\Components\IconEntry::make('is_featured')
                            ->label('Featured')
                            ->boolean()
                            ->trueIcon('heroicon-o-star')
                            ->falseIcon('heroicon-o-x-mark')
                            ->trueColor('warning')
                            ->falseColor('gray'),
                    ])->columns(2),

                Infolists\Components\Section::make('Image')
                    ->schema([
                        Infolists\Components\ImageEntry::make('thumbnail')
                            ->height(200),
                    ]),

                Infolists\Components\Section::make('Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('about')
                            ->label('Description')
                            ->prose()
                            ->columnSpanFull(),
                        // Infolists\Components\TextEntry::make('service_time')
                        //     ->label('Service Time')
                        //     ->suffix(' minutes')
                        //     ->icon('heroicon-o-clock'),
                        Infolists\Components\TextEntry::make('rate')
                            ->label('Rating')
                            ->formatStateUsing(fn (string $state): string => $state . ' / 5 ⭐')
                            ->icon('heroicon-o-star'),
                        Infolists\Components\TextEntry::make('stock')
                            ->label('Stock')
                            ->badge()
                            ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                        Infolists\Components\TextEntry::make('count_click')
                            ->label('Jumlah Transaksi')
                            ->badge()
                            ->color('info'),
                    ])->columns(2),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->icon('heroicon-o-calendar'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->icon('heroicon-o-clock'),
                    ])->columns(2)->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
