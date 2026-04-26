<?php

namespace App\Repositories;

use App\Models\Banner;

class BannerRepository
{
    public function getActive(?string $type = null)
    {
        return Banner::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('published_at')
                ->orWhere('published_at', '<=', now());
            })
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()
            ->get();
    }

    public function find(int $id): ?Banner
    {
        return Banner::find($id);
    }
}