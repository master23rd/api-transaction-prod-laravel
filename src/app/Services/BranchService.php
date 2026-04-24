<?php

namespace App\Services;


use App\Models\Branch;
use App\Models\BranchTimeSlot;
use App\Repositories\BranchRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class BranchService
{
    public function __construct(
        protected BranchRepository $branchRepository
    ) {}

    public function getPaginated(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->branchRepository->getPaginated($filters, $perPage);
    }

    public function getBranch(int $id): ?Branch
    {
        return $this->branchRepository->findById($id);
    }

    public function getPopular(int $limit): Collection
    {
        return $this->branchRepository->getPopular($limit);
    }

    public function getNewest(int $limit): Collection
    {
        return $this->branchRepository->getNewest($limit);
    }

    public function getByCity(int $cityId, int $limit): Collection
    {
        return $this->branchRepository->getByCity($cityId, $limit);
    }

    public function formatBranchListItem(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
            'thumbnail' => $branch->thumbnail,
            'thumbnail_url' => $branch->thumbnail ? url(Storage::url($branch->thumbnail)) : null,
            'about' => $branch->about,
            'facilities' => $branch->facilities ?? [],
            'phone_number' => $branch->phone_number,
            'email' => $branch->email,
            'bank_name' => $branch->bank_name,
            'bank_account_number' => $branch->bank_account_number,
            'bank_account_name' => $branch->bank_account_name,
            'city' => $branch->city ? [
                'id' => $branch->city->id,
                'name' => $branch->city->name,
            ] : null,
        ];
    }

    public function formatBranchResponse(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
            'thumbnail' => $branch->thumbnail,
            'thumbnail_url' => $branch->thumbnail ? url(Storage::url($branch->thumbnail)) : null,
            'photos' => $branch->photos ?? [],
            'photos_url' => $this->formatPhotosUrl($branch->photos),
            'about' => $branch->about,
            'facilities' => $branch->facilities ?? [],
            'manager_name' => $branch->manager_name,
            'phone_number' => $branch->phone_number,
            'email' => $branch->email,
            'bank_name' => $branch->bank_name,
            'bank_account_number' => $branch->bank_account_number,
            'bank_account_name' => $branch->bank_account_name,
            'city' => $branch->city ? [
                'id' => $branch->city->id,
                'name' => $branch->city->name,
                'slug' => $branch->city->slug,
            ] : null,
            'time_slots' => $branch->timeSlots ? $branch->timeSlots->map(fn ($slot) => [
                'id' => $slot->id,
                'day_of_week' => $slot->day_of_week,
                'day_name' => BranchTimeSlot::getDayName($slot->day_of_week),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'is_active' => $slot->is_active,
            ])->toArray() : [],
            'created_at' => $branch->created_at?->toISOString(),
            'updated_at' => $branch->updated_at?->toISOString(),
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
