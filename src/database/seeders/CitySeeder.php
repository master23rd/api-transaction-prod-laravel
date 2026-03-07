<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            'Jakarta',
            'Bandung',
            'Surabaya',
            'Yogyakarta',
            'Bali',
            'Medan',
            'Semarang',
            'Makassar',
            'Malang',
            'Solo',
        ];

        foreach ($cities as $name) {
            City::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'photo' => null,
                ]
            );
        }
    }
}
