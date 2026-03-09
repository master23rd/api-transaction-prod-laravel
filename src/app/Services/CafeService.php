<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\CafeTimeSlot;
use App\Repositories\CafeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class CafeService
{
    public function __construct(
        protected CafeRepository $cafeRepository
    ) {}

    public function getPaginated(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->cafeRepository->getPaginated($filters, $perPage);
    }

    public function getCafe(int $id): ?Cafe
    {
        return $this->cafeRepository->findById($id);
    }

    public function getPopular(int $limit): Collection
    {
        return $this->cafeRepository->getPopular($limit);
    }

    public function getNewest(int $limit): Collection
    {
        return $this->cafeRepository->getNewest($limit);
    }

    public function getByCity(int $cityId, int $limit): Collection
    {
        return $this->cafeRepository->getByCity($cityId, $limit);
    }

    public function formatCafeListItem(Cafe $cafe): array
    {
        return [
            'id' => $cafe->id,
            'name' => $cafe->name,
            'slug' => $cafe->slug,
            'thumbnail' => $cafe->thumbnail,
            'thumbnail_url' => $cafe->thumbnail ? url(Storage::url($cafe->thumbnail)) : null,
            'about' => $cafe->about,
            'facilities' => $cafe->facilities ?? [],
            'city' => $cafe->city ? [
                'id' => $cafe->city->id,
                'name' => $cafe->city->name,
            ] : null,
        ];
    }

    public function formatCafeResponse(Cafe $cafe): array
    {
        return [
            'id' => $cafe->id,
            'name' => $cafe->name,
            'slug' => $cafe->slug,
            'thumbnail' => $cafe->thumbnail,
            'thumbnail_url' => $cafe->thumbnail ? url(Storage::url($cafe->thumbnail)) : null,
            'photos' => $cafe->photos ?? [],
            'photos_url' => $this->formatPhotosUrl($cafe->photos),
            'about' => $cafe->about,
            'facilities' => $cafe->facilities ?? [],
            'manager_name' => $cafe->manager_name,
            'city' => $cafe->city ? [
                'id' => $cafe->city->id,
                'name' => $cafe->city->name,
                'slug' => $cafe->city->slug,
            ] : null,
            'time_slots' => $cafe->timeSlots ? $cafe->timeSlots->map(fn ($slot) => [
                'id' => $slot->id,
                'day_of_week' => $slot->day_of_week,
                'day_name' => CafeTimeSlot::getDayName($slot->day_of_week),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'is_active' => $slot->is_active,
            ])->toArray() : [],
            'created_at' => $cafe->created_at?->toISOString(),
            'updated_at' => $cafe->updated_at?->toISOString(),
        ];
    }

    protected function formatPhotosUrl(?array $photos): array
    {
        if (!$photos) {
            return [];
        }

        return array_map(fn ($photo) => url(Storage::url($photo)), $photos);
    }
}
