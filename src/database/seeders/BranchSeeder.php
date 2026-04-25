<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\City;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Koperasi Sadulur Mujur Pusat', 
                'city' => 'Bandung', 
                'about' => 'Koperasi Cabang Utama', 
                'facilities' => ['wifi','meeting', 'parking', 'ac', 'toilet'], 
                'manager_name' => 'Bapak Sidiq',
                'phone_number' => '62895627540107',
                'email' => 'sadulurjadimujur@gmail.com',
                'bank_name' => 'BJB',
                'bank_account_number' => '0157235435001',
                'bank_account_name' => 'koperasi sadulur mujur'
            ],
        ];

        foreach ($branches as $branch) {
            $city = City::where('slug', $branch['city'])->first();

            if ($city) {
                Branch::firstOrCreate(
                    ['slug' => Str::slug($branch['name'])],
                    [
                        'name' => $branch['name'],
                        'slug' => Str::slug($branch['name']),
                        'thumbnail' => null,
                        'photos' => null,
                        'city_id' => $city->id,
                        'about' => $branch['about'],
                        'facilities' => $branch['facilities'],
                        'manager_name' => $branch['manager_name'],
                        'phone_number' => $branch['phone_number'],
                        'email' => $branch['email'],
                        'bank_name' => $branch['bank_name'],
                        'bank_account_number' => $branch['bank_account_number'],
                        'bank_account_name' => $branch['bank_account_name']
                    ]
                );
            }
        }
    }
}
