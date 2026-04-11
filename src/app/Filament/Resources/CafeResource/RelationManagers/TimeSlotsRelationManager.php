<?php

namespace App\Filament\Resources\CafeResource\RelationManagers;

use App\Models\CafeTimeSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TimeSlotsRelationManager extends RelationManager
{
    protected static string $relationship = 'timeSlots';

    protected static ?string $title = 'Operating Hours';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('day_of_week')
                    ->label('Day of Week')
                    ->options(CafeTimeSlot::getDayOptions())
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('start_time')
                    ->label('Start Time')
                    ->options(self::getTimeOptions())
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('end_time')
                    ->label('End Time')
                    ->options(self::getTimeOptions())
                    ->required()
                    ->native(false),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn (int $state) => CafeTimeSlot::getDayName($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Open')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Close')
                    ->time('H:i'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('day_of_week')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getTimeOptions(): array
    {
        $options = [];
        for ($hour = 6; $hour <= 23; $hour++) {
            $time = sprintf('%02d:00:00', $hour);
            $label = sprintf('%02d:00', $hour);
            $options[$time] = $label;

            // Add half hour option
            $timeHalf = sprintf('%02d:30:00', $hour);
            $labelHalf = sprintf('%02d:30', $hour);
            $options[$timeHalf] = $labelHalf;
        }
        return $options;
    }
}
