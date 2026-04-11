<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CafeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CafeController extends Controller
{
    public function __construct(
        protected CafeService $cafeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'city_id' => 'nullable|integer|exists:cities,id',
            'search' => 'nullable|string|max:100',
            'facilities' => 'nullable|array',
            'facilities.*' => 'string',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $filters = $request->only(['city_id', 'search', 'facilities']);
        $perPage = $request->input('per_page', 10);

        $cafes = $this->cafeService->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'cafes' => $cafes->map(fn ($cafe) => $this->cafeService->formatCafeListItem($cafe)),
                'pagination' => [
                    'current_page' => $cafes->currentPage(),
                    'last_page' => $cafes->lastPage(),
                    'per_page' => $cafes->perPage(),
                    'total' => $cafes->total(),
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $cafe = $this->cafeService->getCafe($id);

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'Cafe not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->cafeService->formatCafeResponse($cafe),
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $cafes = $this->cafeService->getPopular($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'cafes' => $cafes->map(fn ($cafe) => $this->cafeService->formatCafeListItem($cafe)),
            ],
        ]);
    }

    public function newest(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $cafes = $this->cafeService->getNewest($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'cafes' => $cafes->map(fn ($cafe) => $this->cafeService->formatCafeListItem($cafe)),
            ],
        ]);
    }

    public function byCity(Request $request, int $cityId): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $cafes = $this->cafeService->getByCity($cityId, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'cafes' => $cafes->map(fn ($cafe) => $this->cafeService->formatCafeListItem($cafe)),
            ],
        ]);
    }
}
