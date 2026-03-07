<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository
{
    public function getByUser(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->applyFilters(Transaction::query(), $filters)
            ->where('user_id', $userId)
            ->with(['cafe', 'details.product'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Transaction
    {
        return Transaction::with([
            'cafe',
            'user',
            'details.product.category',
            'details.options.productOption',
            'walletTransaction',
        ])->find($id);
    }

    public function findByIdAndUser(int $id, int $userId): ?Transaction
    {
        return Transaction::with([
            'cafe',
            'details.product.category',
            'details.options.productOption',
            'walletTransaction',
        ])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }

    public function getActiveByUser(int $userId): Collection
    {
        return Transaction::with(['cafe', 'details.product'])
            ->where('user_id', $userId)
            ->whereNotIn('order_status', ['finished', 'cancelled'])
            ->latest()
            ->get();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['order_status'])) {
            $query->where('order_status', $filters['order_status']);
        }

        if (!empty($filters['cafe_id'])) {
            $query->where('cafe_id', $filters['cafe_id']);
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
