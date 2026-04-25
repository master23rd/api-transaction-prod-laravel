<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->confirmed(),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->requiredWith('password')
                            ->maxLength(255),

                         // Activate Fomr
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('is_verified')
                            ->label('Verified User')
                            ->helperText('User harus verified untuk bisa login')
                            ->default(false)
                            ->inline(false),
                        
                        Forms\Components\Toggle::make('is_2fa_enabled')
                            ->label('2FA Mode')
                            ->helperText('Aktifkan Verifikasi 2 arah agar lebih aman')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Section::make('Profile Information')
                    ->schema([
                        Forms\Components\FileUpload::make('photo')
                            ->image()
                            ->directory('users')
                            ->circleCropper()
                            ->disk('public')
                            ->visibility('public')
                            ->preserveFilenames(false)
                            ->imageResizeTargetWidth(300)
                            ->imageEditor()
                            ->previewable(true) // ✅ penting
                            ->openable() // klik buka
                            ->downloadable(), // optional,

                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('KTP Information')
                    ->relationship('detail') // ✅ hanya untuk user_details
                    ->schema([
                        Forms\Components\FileUpload::make('ktp_photos')
                            ->label('Upload KTP')
                            ->image()
                            ->directory('ktp')
                            ->disk('public')
                            ->visibility('public')
                            ->imageResizeTargetWidth(800)
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->imageEditor()
                            ->previewable(true) // ✅ penting
                            ->openable() // klik buka
                            ->downloadable(), // optional,,
                    ])
                    ->columns(1),


                Forms\Components\Section::make('Role & Verification')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->options(
                                Role::whereIn('name', ['cafe_manager','branch_manager','customer'])->pluck('name', 'id')
                            )
                            ->preload()
                            ->required(),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->circular()
                    ->extraImgAttributes([
                        'class' => 'cursor-pointer hover:scale-105 transition'
                    ])->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cafe_manager' => 'warning',
                        'branch_manager' => 'warning',
                        'store_manager' => 'warning',
                        'customer' => 'success',
                        'admin' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cafe_manager' => 'Manager',
                        'branch_manager' => 'Manager',
                        'store_manager' => 'Manager',
                        'customer' => 'Customer',
                        'admin' => 'Admin',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_2fa_enabled')
                    ->label('2FA')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verifikasi Akun')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->options([
                        'cafe_manager' => 'Manager',
                        'branch_manager' => 'Manager',
                        'store_manager' => 'Manager',
                        'customer' => 'Customer',
                    ])
                    ->preload()
                    ->label('Role'),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable()
                    ->trueLabel('Verified')
                    ->falseLabel('Not Verified'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== 1),
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
                Infolists\Components\Section::make('Profile')
                    ->schema([
                        Infolists\Components\ImageEntry::make('photo')
                            ->circular()
                            ->url(fn ($record) => $record->photo ? asset('storage/' . $record->yphoto) : null)
                            ->openUrlInNewTab() // ✅ ini bikin clickable
                            ->extraImgAttributes([
                                'class' => 'cursor-pointer hover:scale-105 transition'
                            ])
                            ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF&size=200'),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('name')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight('bold'),
                            Infolists\Components\TextEntry::make('email')
                                ->icon('heroicon-o-envelope'),
                            Infolists\Components\TextEntry::make('phone')
                                ->icon('heroicon-o-phone')
                                ->default('-'),
                        ]),
                    ])->columns(2),

                Infolists\Components\Section::make('Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('roles.name')
                            ->label('Role')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cafe_manager' => 'warning',
                                'branch_manager' => 'warning',
                                'store_manager' => 'warning',
                                'customer' => 'success',
                                'admin' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'cafe_manager' => 'Manager',
                                'branch_manager' => 'Manager',
                                'store_manager' => 'Manager',
                                'customer' => 'Customer',
                                'admin' => 'Admin',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('gender')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'male' => 'info',
                                'female' => 'pink',
                                default => 'gray',
                            })
                            ->default('-'),
                        Infolists\Components\IconEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-x-circle')
                            ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\IconEntry::make('is_2fa_enabled')
                            ->label('2FA')
                            ->boolean()
                            ->trueIcon('heroicon-o-shield-check')
                            ->falseIcon('heroicon-o-shield-exclamation')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        Infolists\Components\IconEntry::make('is_verified')
                            ->label('Verifikasi Akun')
                            ->boolean()
                            ->trueIcon('heroicon-o-shield-check')
                            ->falseIcon('heroicon-o-shield-exclamation')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])->columns(4),

                Infolists\Components\Section::make('User Detail Information')
                ->schema([
                    Infolists\Components\TextEntry::make('detail.nik')
                        ->label('NIK')
                        ->default('-'),

                    Infolists\Components\TextEntry::make('detail.birth_date')
                        ->label('Birth Date')
                        ->date()
                        ->placeholder('-'),

                    Infolists\Components\TextEntry::make('detail.job')
                        ->label('Job')
                        ->default('-'),

                    Infolists\Components\TextEntry::make('detail.office_name')
                        ->label('Office')
                        ->default('-'),

                    Infolists\Components\TextEntry::make('detail.positions')
                        ->label('Position')
                        ->default('-'),

                    Infolists\Components\TextEntry::make('detail.salary')
                        ->label('Salary')
                        ->default('-'),

                    Infolists\Components\TextEntry::make('detail.martial')
                        ->label('Marital Status')
                        ->badge()
                        ->color('info')
                        ->default('-'),

                    Infolists\Components\TextEntry::make('detail.kids')
                        ->label('Kids')
                        ->default('0'),

                    Infolists\Components\TextEntry::make('detail.contact_person')
                        ->label('Contact Person')
                        ->default('-'),

                    Infolists\Components\TextEntry::make('detail.name_person')
                        ->label('Person Name')
                        ->default('-'),

                    Infolists\Components\TextEntry::make('detail.number_contact_person')
                        ->label('Contact Number')
                        ->default('-'),

                    // ✅ KTP IMAGE
                    Infolists\Components\ImageEntry::make('detail.ktp_photos')
                        ->label('KTP Photo')
                        ->disk('public')
                        ->height(200)
                        ->url(fn ($record) => $record->detail->ktp_photos ? asset('storage/' . $record->detail->ktp_photos) : null)
                        ->openUrlInNewTab() // ✅ ini bikin clickable
                        ->extraImgAttributes([
                            'class' => 'cursor-pointer hover:scale-105 transition'
                        ])
                        ->defaultImageUrl('https://via.placeholder.com/200x120'),
                ])
                ->columns(3),

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
