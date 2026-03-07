<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function getAll(): Collection
    {
        return Category::withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::withCount('products')->find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::withCount('products')
            ->where('slug', $slug)
            ->first();
    }

    public function findByIdentifier(string $identifier): ?Category
    {
        return Category::withCount('products')
            ->where('id', $identifier)
            ->orWhere('slug', $identifier)
            ->first();
    }
}
