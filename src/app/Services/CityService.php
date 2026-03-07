<?php

namespace App\Services;

use App\Models\City;
use App\Repositories\CityRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class CityService
{
    public function __construct(
        protected CityRepository $cityRepository
    ) {}

    public function getAllCities(): Collection
    {
        return $this->cityRepository->getAll();
    }

    public function getCity(string $identifier): ?City
    {
        return $this->cityRepository->findByIdentifier($identifier);
    }

    public function formatCityResponse(City $city): array
    {
        return [
            'id' => $city->id,
            'name' => $city->name,
            'slug' => $city->slug,
            'photo' => $city->photo,
            'photo_url' => $city->photo ? url(Storage::url($city->photo)) : null,
            'cafes_count' => $city->cafes_count ?? $city->cafes()->count(),
            'created_at' => $city->created_at?->toISOString(),
        ];
    }

    public function formatCitiesResponse(Collection $cities): array
    {
        return $cities->map(fn ($city) => $this->formatCityResponse($city))->toArray();
    }
}
