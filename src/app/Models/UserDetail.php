<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $fillable = [
        'user_id',
        'nik',
        'birth_date',
        'job',
        'office_name',
        'positions',
        'salary',
        'martial',
        'contact_person',
        'name_person',
        'kids',
        'number_contact_person',
        'ktp_photos',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function ktpPhotosUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ktp_photos ? Storage::url($this->ktp_photos) : null,
        );
    }
}