<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CafeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cafes = [
            ['name' => 'Awake Coffee Central', 'city' => 'jakarta', 'about' => 'Our flagship store in the heart of Jakarta. Experience premium coffee in a modern, comfortable setting.', 'facilities' => ['wifi', 'power', 'meeting', 'outdoor', 'parking', 'ac', 'music', 'toilet'], 'manager_name' => 'Ahmad Rizki'],
            ['name' => 'Awake Coffee Sudirman', 'city' => 'jakarta', 'about' => 'Located in the business district, perfect for professionals seeking quality coffee breaks.', 'facilities' => ['wifi', 'power', 'parking', 'ac', 'toilet'], 'manager_name' => 'Dewi Sartika'],
            ['name' => 'Awake Coffee Dago', 'city' => 'bandung', 'about' => 'Nestled in the hills of Bandung with stunning city views and fresh mountain air.', 'facilities' => ['wifi', 'power', 'outdoor', 'music', 'toilet'], 'manager_name' => 'Budi Santoso'],
            ['name' => 'Awake Coffee Braga', 'city' => 'bandung', 'about' => 'Historic location on the famous Braga street, blending heritage with modern coffee culture.', 'facilities' => ['wifi', 'power', 'parking', 'ac', 'music', 'toilet'], 'manager_name' => 'Citra Dewi'],
            ['name' => 'Awake Coffee Tunjungan', 'city' => 'surabaya', 'about' => 'Prime location in Surabaya shopping district with spacious seating.', 'facilities' => ['wifi', 'power', 'meeting', 'parking', 'ac', 'toilet'], 'manager_name' => 'Eko Prasetyo'],
            ['name' => 'Awake Coffee Malioboro', 'city' => 'yogyakarta', 'about' => 'Experience Javanese hospitality with world-class coffee near Malioboro street.', 'facilities' => ['wifi', 'power', 'outdoor', 'ac', 'toilet'], 'manager_name' => 'Fajar Nugroho'],
            ['name' => 'Awake Coffee Seminyak', 'city' => 'bali', 'about' => 'Tropical vibes meet specialty coffee in the heart of Seminyak.', 'facilities' => ['wifi', 'power', 'outdoor', 'parking', 'music', 'toilet'], 'manager_name' => 'Gede Wirawan'],
            ['name' => 'Awake Coffee Ubud', 'city' => 'bali', 'about' => 'Surrounded by rice terraces, offering organic coffee and peaceful ambiance.', 'facilities' => ['wifi', 'power', 'outdoor', 'toilet'], 'manager_name' => 'Nyoman Putra'],
            ['name' => 'Awake Coffee Polonia', 'city' => 'medan', 'about' => 'Serving the finest Sumatran coffee in a cozy urban setting.', 'facilities' => ['wifi', 'power', 'parking', 'ac', 'toilet'], 'manager_name' => 'Hendra Siregar'],
            ['name' => 'Awake Coffee Simpang Lima', 'city' => 'semarang', 'about' => 'Central Semarang location with a blend of modern and traditional Javanese design.', 'facilities' => ['wifi', 'power', 'meeting', 'parking', 'ac', 'toilet'], 'manager_name' => 'Indra Kusuma'],
        ];

        foreach ($cafes as $cafe) {
            $city = City::where('slug', $cafe['city'])->first();

            if ($city) {
                Cafe::firstOrCreate(
                    ['slug' => Str::slug($cafe['name'])],
                    [
                        'name' => $cafe['name'],
                        'slug' => Str::slug($cafe['name']),
                        'thumbnail' => null,
                        'photos' => null,
                        'city_id' => $city->id,
                        'about' => $cafe['about'],
                        'facilities' => $cafe['facilities'],
                        'manager_name' => $cafe['manager_name'],
                    ]
                );
            }
        }
    }
}
