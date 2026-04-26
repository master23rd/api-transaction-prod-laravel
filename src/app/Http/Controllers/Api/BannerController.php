<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(
        protected BannerService $bannerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|in:news,promo',
        ]);

        $banners = $this->bannerService->getActiveBanners(
            $request->input('type')
        );

        return response()->json([
            'success' => true,
            'data' => $banners->map(
                fn ($banner) => $this->bannerService->formatBanner($banner)
            ),
        ]);
    }
}
