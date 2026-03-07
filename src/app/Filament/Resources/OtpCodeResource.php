<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtpCodeResource\Pages;
use App\Models\OtpCode;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OtpCodeResource extends Resource
{
    protected static ?string $model = OtpCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'OTP Code';

    protected static ?string $pluralModelLabel = 'OTP Codes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('OTP Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(6)
                            ->default(fn () => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->required()
                            ->default(now()->addMinutes(5)),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('OTP Code')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_expired')
                    ->label('Status')
                    ->getStateUsing(fn (OtpCode $record): bool => $record->expires_at->isPast())
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->label('Expired'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '>', now())),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired Only')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<=', now())),
            ])
            ->actions([
                Tables\Actions\Action::make('invalidate')
                    ->label('Invalidate')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (OtpCode $record) => $record->update(['expires_at' => now()]))
                    ->visible(fn (OtpCode $record): bool => $record->expires_at->isFuture()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListOtpCodes::route('/'),
            'create' => Pages\CreateOtpCode::route('/create'),
        ];
    }
}
