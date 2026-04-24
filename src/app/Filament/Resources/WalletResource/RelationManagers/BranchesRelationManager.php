<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Branch;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('branch_id')
                    ->label('Branch')
                    ->options(Branch::pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('City'),

                Tables\Columns\TextColumn::make('manager_name'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Attach Branch')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        fn (Builder $query) => $query
                            ->whereDoesntHave('wallets', function ($q) {
                                $q->where('wallet_id', $this->ownerRecord->id);
                            })
                    ),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Detach'),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}