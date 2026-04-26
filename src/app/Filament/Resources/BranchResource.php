<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Str;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    //additional
    protected static ?string $navigationLabel = 'Koperasi';

    protected static ?string $navigationGroup = 'Location';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'Koperasi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Koperasi';
    }
    //end of additional

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
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->placeholder('-6.200000')
                            ->helperText('Contoh: -6.200000'),
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->placeholder('106.816666')
                            ->helperText('Contoh: 106.816666'),
                        Forms\Components\TextInput::make('manager_name')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Images')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail')
                            ->image()
                            ->directory('branches/thumbnails')
                            ->imageEditor(),
                        Forms\Components\FileUpload::make('photos')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->directory('branches/photos')
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
                                // 'power' => 'Power',
                                'meeting' => 'Meeting',
                                'outdoor' => 'Outdoor',
                                'parking' => 'Parking',
                                'ac' => 'AC',
                                // 'music' => 'Music',
                                'toilet' => 'Toilet',
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Contact & Payment')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->placeholder('628123456789')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('bank_account_name')
                            ->label('Account Holder')
                            ->maxLength(255),
                    ])
                    ->columns(2),
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
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('manager_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('phone_number')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank')
                    ->toggleable(),
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
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Branch $record) => $record->id !== 1),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->filter(fn ($record) => $record->id !== 1)->each->delete();
                        }),
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

                Infolists\Components\Section::make('Location')
                    ->schema([
                        Infolists\Components\TextEntry::make('address')
                            ->label('Alamat')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('latitude')
                            ->label('Latitude'),

                        Infolists\Components\TextEntry::make('longitude')
                            ->label('Longitude'),

                        // OPTIONAL: link ke Google Maps
                        Infolists\Components\TextEntry::make('map')
                            ->label('Google Maps')
                            ->formatStateUsing(fn ($record) =>
                                $record->latitude && $record->longitude
                                    ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}"
                                    : '-'
                            )
                            ->url(fn ($record) =>
                                $record->latitude && $record->longitude
                                    ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}"
                                    : null
                            )
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2),

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

                Infolists\Components\Section::make('Contact & Payment')
                    ->schema([
                        Infolists\Components\TextEntry::make('phone_number')
                            ->formatStateUsing(fn ($state) => $state ? "https://wa.me/{$state}" : null)
                            ->url(fn ($state) => $state ? "https://wa.me/{$state}" : null)
                            ->openUrlInNewTab()
                            ->label('WhatsApp')
                            ->icon('heroicon-o-phone'),

                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope'),

                        Infolists\Components\TextEntry::make('bank_name')
                            ->label('Bank'),

                        Infolists\Components\TextEntry::make('bank_account_number')
                            ->label('Account Number'),

                        Infolists\Components\TextEntry::make('bank_account_name')
                            ->label('Account Holder'),
                    ])
                    ->columns(2),

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
            RelationManagers\WalletsRelationManager::class,
            RelationManagers\StoresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'view' => Pages\ViewBranch::route('/{record}'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
