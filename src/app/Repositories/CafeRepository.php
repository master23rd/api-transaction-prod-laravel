<?php

namespace App\Repositories;

use App\Models\Cafe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CafeRepository
{
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->applyFilters(Cafe::query(), $filters)
            ->with(['city'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Cafe
    {
        return Cafe::with(['city', 'timeSlots'])
            ->find($id);
    }

    public function getPopular(int $limit = 5): Collection
    {
        return Cafe::withCount(['transactions' => fn ($q) => $q->where('order_status', 'finished')])
            ->with(['city'])
            ->orderByDesc('transactions_count')
            ->limit($limit)
            ->get();
    }

    public function getNewest(int $limit = 5): Collection
    {
        return Cafe::with(['city'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getByCity(int $cityId, int $limit = 10): Collection
    {
        return Cafe::with(['city'])
            ->where('city_id', $cityId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', '%' . $search . '%');
        }

        if (!empty($filters['facilities'])) {
            foreach ($filters['facilities'] as $facility) {
                $query->whereJsonContains('facilities', $facility);
            }
        }

        return $query;
    }
}
