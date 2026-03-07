<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class City extends Model
{
    protected $fillable = [
        'name',
        'photo',
        'slug',
    ];

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['photo'] ? Storage::url($this->attributes['photo']) : null,
        );
    }

    public function cafes(): HasMany
    {
        return $this->hasMany(Cafe::class);
    }
}
