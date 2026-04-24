<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function getPaginated(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->productRepository->getPaginated($filters, $perPage);
    }

    public function getProduct(string $identifier): ?Product
    {
        return $this->productRepository->findByIdentifier($identifier);
    }

    public function getByCategory(int $categoryId, int $limit): Collection
    {
        return $this->productRepository->getByCategory($categoryId, $limit);
    }

    public function getByCategorySlug(string $categorySlug, int $limit): Collection
    {
        return $this->productRepository->getByCategorySlug($categorySlug, $limit);
    }

    public function getPopular(int $limit): Collection
    {
        return $this->productRepository->getPopular($limit);
    }

    public function getTopRated(int $limit): Collection
    {
        return $this->productRepository->getTopRated($limit);
    }

    public function getFeatured(int $limit): Collection
    {
        return $this->productRepository->getFeatured($limit);
    }

    public function formatProductListItem(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'price_formatted' => 'Rp ' . number_format($product->price, 0, ',', '.'),
            'rate' => $product->rate,
            'thumbnail' => $product->thumbnail,
            'thumbnail_url' => $product->thumbnail ? url(Storage::url($product->thumbnail)) : null,
            'service_time' => $product->service_time,
            'is_featured' => $product->is_featured,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            // ✅ STORE
            'store' => $product->store ? [
                'id' => $product->store->id,
                'name' => $product->store->name,
                'slug' => $product->store->slug,
            ] : null,

            // ✅ BRANCH (via store)
            'branch' => $product->store && $product->store->branch ? [
                'id' => $product->store->branch->id,
                'name' => $product->store->branch->name,
            ] : null,
        ];
    }

    public function formatProductResponse(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'price_formatted' => 'Rp ' . number_format($product->price, 0, ',', '.'),
            'rate' => $product->rate,
            'thumbnail' => $product->thumbnail,
            'thumbnail_url' => $product->thumbnail ? url(Storage::url($product->thumbnail)) : null,
            'about' => $product->about,
            'service_time' => $product->service_time,
            'is_featured' => $product->is_featured,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            // ✅ STORE
            'store' => $product->store ? [
                'id' => $product->store->id,
                'name' => $product->store->name,
                'slug' => $product->store->slug,
            ] : null,

            // ✅ BRANCH (via store)
            'branch' => $product->store && $product->store->branch ? [
                'id' => $product->store->branch->id,
                'name' => $product->store->branch->name,
            ] : null,
            'options' => $this->formatOptions($product->options),
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
        ];
    }

    protected function formatOptions($options): array
    {
        if (!$options) {
            return [];
        }

        // Group options by type
        $grouped = $options->groupBy('type');

        return $grouped->map(function ($items, $type) {
            return [
                'type' => $type,
                'items' => $items->map(fn ($option) => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'price' => $option->price,
                    'price_formatted' => 'Rp ' . number_format($option->price, 0, ',', '.'),
                ])->values()->toArray(),
            ];
        })->values()->toArray();
    }
}
