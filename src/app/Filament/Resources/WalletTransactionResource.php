<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Exports\WalletTransactionExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    // protected static ?string $navigationLabel = 'Wallet Transactions';
    protected static ?string $navigationLabel = 'Transaksi Tabungan';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Information')
                    ->schema([
                        Forms\Components\Select::make('wallet_id')
                            ->relationship('wallet', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name . ' - ' . $record->user->email)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'topup' => 'Setor', //Top Up
                                'payment' => 'Tarik', //Payment
                                // 'refund' => 'Refund',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (Added to Wallet)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Transfer Amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        // Forms\Components\TextInput::make('service_fee')
                        //     ->numeric()
                        //     ->prefix('Rp')
                        //     ->default(0),
                        // Forms\Components\TextInput::make('unique_code')
                        //     ->numeric()
                        //     ->minValue(1)
                        //     ->maxValue(200),
                        Forms\Components\FileUpload::make('proof_of_payment')
                            ->image()
                            ->directory('wallet-proofs'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallet.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'topup' => 'success',
                        'payment' => 'warning',
                        'refund' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'topup' => 'Setor', //'Top Up',
                        'payment' => 'Tarik', //'Payment',
                        'refund' => 'Refund',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Koperasi')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('wallet.account_number')
                    ->label('No Rekening')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Ref Code')
                    ->searchable()
                    ->copyable(),
                // Tables\Columns\TextColumn::make('amount')
                //     ->label('Top Up Amount')
                //     ->money('IDR')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Transfer Amount')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                // Tables\Columns\TextColumn::make('unique_code')
                //     ->label('Unique Code')
                //     ->sortable()
                //     ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\ImageColumn::make('proof_of_payment')
                    ->label('Proof')
                    ->disk('public')
                    ->circular()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // ✅ FILTER TANGGAL
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'],
                                fn ($q) => $q->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when($data['until'],
                                fn ($q) => $q->whereDate('created_at', '<=', $data['until'])
                            );
                    }),

                // ✅ FILTER BRANCH
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Koperasi')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                // ✅ FILTER TYPE
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'topup' => 'Setor',
                        'payment' => 'Tarik',
                    ]),

                // ✅ FILTER STATUS (existing, tapi kita rapihin)
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

            ])
            ->actions([
                Tables\Actions\Action::make('approved')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (WalletTransaction $record): bool => $record->status === 'pending' && ($record->type === 'topup' || $record->type === 'payment'))
                    ->requiresConfirmation()
                    ->action(function (WalletTransaction $record): void {
                        $record->update(['status' => 'approved']);

                        // Update wallet balance for topup - use amount (pure top-up amount)
                        $wallet = $record->wallet;
                        $wallet->update(['balance' => $wallet->balance + $record->amount]);

                        Notification::make()
                            ->title('Transaction approved')
                            ->body('Wallet balance updated: +Rp ' . number_format($record->amount, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (WalletTransaction $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (WalletTransaction $record): void {
                        $record->update(['status' => 'rejected']);

                        Notification::make()
                            ->title('Transaction rejected')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                // ✅ EXPORT EXCEL
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($livewire) {

                        $query = $livewire->getFilteredTableQuery();

                        return Excel::download(
                            new WalletTransactionExport($query),
                            'transaction-report.xlsx'
                        );
                    }),

                // ✅ EXPORT PDF
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document')
                    ->color('danger')
                    ->action(function ($livewire) {

                        $transactions = $livewire->getFilteredTableQuery()
                            ->with(['wallet.user', 'branch'])
                            ->get();

                        $pdf = Pdf::loadView('reports.wallet-transaction-pdf', [
                            'transactions' => $transactions
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'transaction-report.pdf'
                        );
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('wallet.user.name')
                            ->label('Customer Name'),
                        Infolists\Components\TextEntry::make('wallet.user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('wallet.account_number')
                            ->label('No Rekening')
                            ->icon('heroicon-o-credit-card'),
                    ])->columns(3),

                Infolists\Components\Section::make('Transaction Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference_code')
                            ->label('Reference Code')
                            ->badge()
                            ->copyable(),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'topup' => 'success',
                                'payment' => 'warning',
                                'refund' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'topup' => 'Setor', //'Top Up',
                                'payment' => 'Tarik', //'Payment',
                                'refund' => 'Refund',
                                default => ucfirst($state),
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Top Up Amount')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Transfer Amount')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->prose()
                            ->columnSpanFull(),
                        // Infolists\Components\TextEntry::make('service_fee')
                        //     ->money('IDR'),
                        // Infolists\Components\TextEntry::make('unique_code'),
                    ])->columns(3),

                Infolists\Components\Section::make('Proof of Payment')
                    ->schema([
                        Infolists\Components\ImageEntry::make('proof_of_payment')
                            ->disk('public')
                            ->height(300)
                            ->extraImgAttributes([
                                'class' => 'rounded-lg cursor-pointer',
                            ])
                            ->url(fn ($record) => $record->proof_of_payment 
                                ? asset('storage/' . $record->proof_of_payment) 
                                : null
                            )
                            ->openUrlInNewTab(),
                    ])
                    ->visible(fn (WalletTransaction $record): bool => $record->proof_of_payment !== null),
                Infolists\Components\Section::make('Actions')
                    ->schema([
                        Actions::make([                    
                            // ✅ APPROVE
                            Action::make('approve')
                                ->label('Approve')
                                ->icon('heroicon-o-check')
                                ->color('success')
                                ->visible(fn (WalletTransaction $record) => $record->status === 'pending')
                                ->requiresConfirmation()
                                ->action(function (WalletTransaction $record) {

                                    $wallet = $record->wallet;

                                    if (!$wallet) return;

                                    // LOGIC SALDO
                                    if ($record->type === 'topup') {
                                        $wallet->increment('balance', $record->total_amount);
                                    } elseif ($record->type === 'payment') {

                                        if ($wallet->balance < $record->total_amount) {
                                            Notification::make()
                                                ->title('Saldo tidak cukup')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        $wallet->decrement('balance', $record->total_amount);
                                    }

                                    $record->update([
                                        'status' => 'approved',
                                        'branch_id' => $wallet->branch_id,
                                    ]);

                                    Notification::make()
                                        ->title('Transaction approved')
                                        ->success()
                                        ->send();
                                }),

                            // ❌ REJECT
                            Action::make('reject')
                                ->label('Reject')
                                ->icon('heroicon-o-x-mark')
                                ->color('danger')
                                ->visible(fn (WalletTransaction $record) => $record->status === 'pending')
                                ->requiresConfirmation()
                                ->action(function (WalletTransaction $record) {

                                    $record->update([
                                        'status' => 'rejected',
                                    ]);

                                    Notification::make()
                                        ->title('Transaction rejected')
                                        ->success()
                                        ->send();
                                }),

                        ])
                    ])
                    ->visible(fn (WalletTransaction $record) => $record->status === 'pending'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['wallet.user', 'branch']);
    }
}
