<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Branch extends Model
{
    use SoftDeletes;

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

    protected $casts = [
        'photos' => 'array',
        'facilities' => 'array',
    ];

    // protected $guarded = [];

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

    // wallet relation
    public function walletBranches()
    {
        return $this->hasMany(WalletBranch::class);
    }

    public function wallets()
    {
        return $this->belongsToMany(Wallet::class, 'wallet_branch');
    }

    // store relation
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    // time slots
    public function timeSlots()
    {
        return $this->hasMany(BranchTimeSlot::class);
    }
}
