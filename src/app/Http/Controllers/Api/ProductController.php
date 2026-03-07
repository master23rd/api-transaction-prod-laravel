<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'search' => 'nullable|string|max:100',
            'min_price' => 'nullable|integer|min:0',
            'max_price' => 'nullable|integer|min:0',
            'min_rate' => 'nullable|numeric|min:0|max:5',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $filters = $request->only(['category_id', 'search', 'min_price', 'max_price', 'min_rate']);
        $perPage = $request->input('per_page', 10);

        $products = $this->productService->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products->map(fn ($product) => $this->productService->formatProductListItem($product)),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ],
        ]);
    }

    public function show(string $identifier): JsonResponse
    {
        $product = $this->productService->getProduct($identifier);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->productService->formatProductResponse($product),
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $products = $this->productService->getPopular($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products->map(fn ($product) => $this->productService->formatProductListItem($product)),
            ],
        ]);
    }

    public function topRated(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $products = $this->productService->getTopRated($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products->map(fn ($product) => $this->productService->formatProductListItem($product)),
            ],
        ]);
    }

    public function featured(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $products = $this->productService->getFeatured($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products->map(fn ($product) => $this->productService->formatProductListItem($product)),
            ],
        ]);
    }

    public function byCategory(Request $request, int $categoryId): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $products = $this->productService->getByCategory($categoryId, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products->map(fn ($product) => $this->productService->formatProductListItem($product)),
            ],
        ]);
    }

    public function byCategorySlug(Request $request, string $categorySlug): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $products = $this->productService->getByCategorySlug($categorySlug, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products->map(fn ($product) => $this->productService->formatProductListItem($product)),
            ],
        ]);
    }
}
