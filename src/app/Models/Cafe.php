<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Cafe extends Model
{
    protected $fillable = [
        'name',
        'thumbnail',
        'photos',
        'slug',
        'city_id',
        'about',
        'facilities',
        'manager_name',
        'phone_number',
        'email',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'facilities' => 'array',
        ];
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['thumbnail'] ? Storage::url($this->attributes['thumbnail']) : null,
        );
    }

    protected function photosUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $photos = $this->attributes['photos'] ?? null;
                if (!$photos) {
                    return [];
                }
                $photosArray = json_decode($photos, true) ?? [];

                return array_map(fn ($photo) => Storage::url($photo), $photosArray);
            },
        );
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(CafeTimeSlot::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
