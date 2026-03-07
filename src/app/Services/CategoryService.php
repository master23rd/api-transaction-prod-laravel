<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    public function getAllCategories(): Collection
    {
        return $this->categoryRepository->getAll();
    }

    public function getCategory(string $identifier): ?Category
    {
        return $this->categoryRepository->findByIdentifier($identifier);
    }

    public function formatCategoryResponse(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'products_count' => $category->products_count ?? $category->products()->count(),
            'created_at' => $category->created_at?->toISOString(),
        ];
    }

    public function formatCategoriesResponse(Collection $categories): array
    {
        return $categories->map(fn ($category) => $this->formatCategoryResponse($category))->toArray();
    }
}
