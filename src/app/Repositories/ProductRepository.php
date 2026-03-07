<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->applyFilters(Product::query(), $filters)
            ->with(['category'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return Product::with(['category', 'options'])
            ->find($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::with(['category', 'options'])
            ->where('slug', $slug)
            ->first();
    }

    public function findByIdentifier(string $identifier): ?Product
    {
        return Product::with(['category', 'options'])
            ->where('id', $identifier)
            ->orWhere('slug', $identifier)
            ->first();
    }

    public function getByCategory(int $categoryId, int $limit = 10): Collection
    {
        return Product::with(['category'])
            ->where('category_id', $categoryId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getByCategorySlug(string $categorySlug, int $limit = 10): Collection
    {
        return Product::with(['category'])
            ->whereHas('category', fn ($query) => $query->where('slug', $categorySlug))
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getPopular(int $limit = 5): Collection
    {
        return Product::withCount('transactionDetails')
            ->with(['category'])
            ->orderByDesc('transaction_details_count')
            ->limit($limit)
            ->get();
    }

    public function getTopRated(int $limit = 5): Collection
    {
        return Product::with(['category'])
            ->orderByDesc('rate')
            ->limit($limit)
            ->get();
    }

    public function getFeatured(int $limit = 10): Collection
    {
        return Product::with(['category'])
            ->where('is_featured', true)
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', '%' . $search . '%');
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['min_rate'])) {
            $query->where('rate', '>=', $filters['min_rate']);
        }

        return $query;
    }
}
