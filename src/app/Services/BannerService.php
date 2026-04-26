<?php

namespace App\Services;

use App\Repositories\BannerRepository;

class BannerService
{
    public function __construct(
        protected BannerRepository $bannerRepository
    ) {}

    public function getActiveBanners(?string $type = null)
    {
        return $this->bannerRepository->getActive($type);
    }

    public function formatBanner($banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'description' => $banner->description,
            'image' => $banner->image_url,
            'type' => $banner->type,
            'link' => $banner->link,
            'published_at' => $banner->published_at,
        ];
    }
}
