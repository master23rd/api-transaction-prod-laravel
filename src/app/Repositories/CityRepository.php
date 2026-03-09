<?php

namespace App\Repositories;

use App\Models\City;
use Illuminate\Database\Eloquent\Collection;

class CityRepository
{
    public function getAll(): Collection
    {
        return City::withCount('cafes')
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?City
    {
        return City::withCount('cafes')->find($id);
    }

    public function findBySlug(string $slug): ?City
    {
        return City::withCount('cafes')
            ->where('slug', $slug)
            ->first();
    }

    public function findByIdentifier(string $identifier): ?City
    {
        return City::withCount('cafes')
            ->where('id', $identifier)
            ->orWhere('slug', $identifier)
            ->first();
    }
}
