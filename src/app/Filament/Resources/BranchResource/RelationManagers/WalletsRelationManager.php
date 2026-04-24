<?php

namespace App\Filament\Resources\BranchResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Wallet;

class WalletsRelationManager extends RelationManager
{
    protected static string $relationship = 'wallets';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('wallet_id')
                    ->label('Wallet')
                    ->options(
                        Wallet::with('user')
                            ->get()
                            ->mapWithKeys(fn ($wallet) => [
                                $wallet->id => $wallet->user->name . ' (Rp ' . number_format($wallet->balance, 0, ',', '.') . ')'
                            ])
                    )
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('user.name')
                //     ->formatStateUsing(fn ($record) => $record->user->name . ' - Rp ' . number_format($record->balance)),

                Tables\Columns\TextColumn::make('balance')
                    ->money('IDR')
                    ->label('Balance'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Connect Wallet')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        fn (Builder $query) => $query->with('user')
                    ),
                    //select only non branch wallet
                    // ->recordSelectOptionsQuery(
                    //     fn (Builder $query) => $query->whereDoesntHave('branches', function ($q) {
                    //         $q->where('branch_id', $this->ownerRecord->id);
                    //     })
                    // )
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Disconnect'),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
