<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CafeResource\Pages;
use App\Filament\Resources\CafeResource\RelationManagers;
use App\Models\Cafe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CafeResource extends Resource
{
    protected static ?string $model = Cafe::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Location';

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
                        Forms\Components\Select::make('city_id')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('manager_name')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Images')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail')
                            ->image()
                            ->directory('cafes/thumbnails')
                            ->imageEditor(),
                        Forms\Components\FileUpload::make('photos')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->directory('cafes/photos')
                            ->imageEditor()
                            ->maxFiles(10),
                    ])->columns(2),

                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\Textarea::make('about')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\CheckboxList::make('facilities')
                            ->options([
                                'wifi' => 'WiFi',
                                'power' => 'Power',
                                'meeting' => 'Meeting',
                                'outdoor' => 'Outdoor',
                                'parking' => 'Parking',
                                'ac' => 'AC',
                                'music' => 'Music',
                                'toilet' => 'Toilet',
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
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
                Tables\Columns\TextColumn::make('city.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('manager_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city')
                    ->relationship('city', 'name')
                    ->preload()
                    ->searchable(),
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
                        Infolists\Components\TextEntry::make('city.name')
                            ->label('City')
                            ->icon('heroicon-o-map-pin'),
                        Infolists\Components\TextEntry::make('manager_name')
                            ->label('Manager')
                            ->icon('heroicon-o-user'),
                    ])->columns(2),

                Infolists\Components\Section::make('Images')
                    ->schema([
                        Infolists\Components\ImageEntry::make('thumbnail')
                            ->label('Thumbnail')
                            ->height(200),
                        Infolists\Components\ImageEntry::make('photos')
                            ->label('Gallery Photos')
                            ->height(150)
                            ->stacked()
                            ->limit(5)
                            ->limitedRemainingText(),
                    ])->columns(2),

                Infolists\Components\Section::make('About')
                    ->schema([
                        Infolists\Components\TextEntry::make('about')
                            ->prose()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Facilities')
                    ->schema([
                        Infolists\Components\TextEntry::make('facilities')
                            ->badge()
                            ->separator(',')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'wifi' => 'WiFi',
                                'power' => 'Power',
                                'meeting' => 'Meeting',
                                'outdoor' => 'Outdoor',
                                'parking' => 'Parking',
                                'ac' => 'AC',
                                'music' => 'Music',
                                'toilet' => 'Toilet',
                                default => $state,
                            })
                            ->color('success')
                            ->columnSpanFull(),
                    ]),

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
            RelationManagers\TimeSlotsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCafes::route('/'),
            'create' => Pages\CreateCafe::route('/create'),
            'view' => Pages\ViewCafe::route('/{record}'),
            'edit' => Pages\EditCafe::route('/{record}/edit'),
        ];
    }
}
