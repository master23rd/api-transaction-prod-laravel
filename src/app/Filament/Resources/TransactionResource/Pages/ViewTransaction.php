<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\WalletTransaction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_preparing')
                ->label('Mark as Preparing')
                ->icon('heroicon-o-fire')
                ->color('info')
                ->visible(fn () => $this->record->order_status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Mark Order as Preparing')
                ->modalDescription('Are you sure you want to mark this order as preparing?')
                ->action(function () {
                    $this->record->update(['order_status' => 'preparing']);

                    Notification::make()
                        ->title('Order marked as preparing')
                        ->success()
                        ->send();

                    $this->refreshFormData(['order_status']);
                }),
            Actions\Action::make('mark_finished')
                ->label('Mark as Finished')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->order_status === 'preparing')
                ->requiresConfirmation()
                ->modalHeading('Mark Order as Finished')
                ->modalDescription('Are you sure you want to mark this order as finished?')
                ->action(function () {
                    $this->record->update(['order_status' => 'finished']);

                    Notification::make()
                        ->title('Order marked as finished')
                        ->success()
                        ->send();

                    $this->refreshFormData(['order_status']);
                }),
            Actions\Action::make('mark_cancelled')
                ->label('Mark as Cancelled')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->order_status, ['pending', 'preparing']))
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('Are you sure you want to cancel this order? The amount will be refunded to the customer\'s wallet.')
                ->action(function () {
                    $transaction = $this->record;

                    // Update order status
                    $transaction->update([
                        'order_status' => 'cancelled',
                        'payment_status' => $transaction->payment_status === 'paid' ? 'refunded' : 'cancelled',
                    ]);

                    // Refund to wallet if payment was made
                    if ($transaction->payment_method === 'wallet' && $transaction->payment_status === 'refunded') {
                        $wallet = $transaction->user->wallet;

                        if ($wallet) {
                            // Restore wallet balance
                            $wallet->update(['balance' => $wallet->balance + $transaction->grand_total_amount]);

                            // Create refund wallet transaction
                            WalletTransaction::create([
                                'wallet_id' => $wallet->id,
                                'transaction_id' => $transaction->id,
                                'amount' => $transaction->grand_total_amount,
                                'total_amount' => $transaction->grand_total_amount,
                                'type' => 'refund',
                                'status' => 'approved',
                                'service_fee' => 0,
                            ]);
                        }
                    }

                    Notification::make()
                        ->title('Order cancelled')
                        ->body('Amount refunded: Rp ' . number_format($transaction->grand_total_amount, 0, ',', '.'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['order_status', 'payment_status']);
                }),
            Actions\EditAction::make(),
        ];
    }
}
