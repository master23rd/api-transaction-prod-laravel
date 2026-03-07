<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CityService;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function __construct(
        protected CityService $cityService
    ) {}

    public function index(): JsonResponse
    {
        $cities = $this->cityService->getAllCities();

        return response()->json([
            'success' => true,
            'data' => $this->cityService->formatCitiesResponse($cities),
        ]);
    }

    public function show(string $identifier): JsonResponse
    {
        $city = $this->cityService->getCity($identifier);

        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->cityService->formatCityResponse($city),
        ]);
    }
}
