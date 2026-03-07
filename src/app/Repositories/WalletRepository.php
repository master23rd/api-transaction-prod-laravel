<?php

namespace App\Repositories;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WalletRepository
{
    public function findByUserId(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function getTransactions(int $walletId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->applyFilters(WalletTransaction::query(), $filters)
            ->where('wallet_id', $walletId)
            ->with(['transaction.cafe'])
            ->latest()
            ->paginate($perPage);
    }

    public function findTransactionById(int $id): ?WalletTransaction
    {
        return WalletTransaction::with(['wallet.user', 'transaction.cafe'])
            ->find($id);
    }

    public function findTransactionByIdAndWallet(int $id, int $walletId): ?WalletTransaction
    {
        return WalletTransaction::with(['transaction.cafe'])
            ->where('id', $id)
            ->where('wallet_id', $walletId)
            ->first();
    }

    public function createTransaction(array $data): WalletTransaction
    {
        return WalletTransaction::create($data);
    }

    public function updateTransaction(WalletTransaction $transaction, array $data): WalletTransaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }

    public function updateWalletBalance(Wallet $wallet, float $amount): Wallet
    {
        $wallet->increment('balance', $amount);
        return $wallet->fresh();
    }

    public function getPendingTopups(int $walletId): LengthAwarePaginator
    {
        return WalletTransaction::where('wallet_id', $walletId)
            ->where('type', 'topup')
            ->where('status', 'pending')
            ->latest()
            ->paginate(10);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
