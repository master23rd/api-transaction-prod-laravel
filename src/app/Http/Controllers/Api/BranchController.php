<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branchService
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

        $branches = $this->branchService->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'branches' => $branches->map(fn ($branch) => $this->branchService->formatBranchListItem($branch)),
                'pagination' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'per_page' => $branches->perPage(),
                    'total' => $branches->total(),
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $branch = $this->branchService->getBranch($id);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->branchService->formatBranchResponse($branch),
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $branches = $this->branchService->getPopular($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'branches' => $branches->map(fn ($branch) => $this->branchService->formatBranchListItem($branch)),
            ],
        ]);
    }

    public function newest(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $branches = $this->branchService->getNewest($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'branches' => $branches->map(fn ($branch) => $this->branchService->formatBranchListItem($branch)),
            ],
        ]);
    }

    public function byCity(Request $request, int $cityId): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $branches = $this->branchService->getByCity($cityId, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'branches' => $branches->map(fn ($branch) => $this->branchService->formatBranchListItem($branch)),
            ],
        ]);
    }
}
