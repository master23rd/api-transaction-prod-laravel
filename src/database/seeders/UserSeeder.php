<?php

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // ========================
        // CAFE MANAGERS
        // ========================
        $managers = [
            ['name' => 'Ahmad Rizki', 'email' => 'ahmad@koperasi.com'],
            ['name' => 'Dewi Sartika', 'email' => 'dewi@koperasi.com'],
            ['name' => 'Budi Santoso', 'email' => 'budi@koperasi.com'],
            ['name' => 'Citra Dewi', 'email' => 'citra@koperasi.com'],
            ['name' => 'Eko Prasetyo', 'email' => 'eko@koperasi.com'],
        ];

        foreach ($managers as $manager) {
            $user = User::firstOrCreate(
                ['email' => $manager['email']],
                [
                    'name' => $manager['name'],
                    'password' => Hash::make('password'),
                    'phone' => $faker->phoneNumber,
                    'gender' => $faker->randomElement(['male', 'female']),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'is_verified' => true,
                    'is_2fa_enabled' => false,
                ]
            );

            $user->assignRole('cafe_manager');

            // 🔥 USER DETAIL
            UserDetail::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $faker->unique()->numerify('################'),
                    'birth_date' => $faker->date(),
                    'job' => 'Manager',
                    'office_name' => 'Koperasi Nusantara',
                    'positions' => 'Cafe Manager',
                    'salary' => $faker->numberBetween(5000000, 15000000),
                    'marital' => $faker->randomElement(['single', 'married']),
                    'contact_person' => $faker->name,
                    'name_person' => $faker->name,
                    'kids' => $faker->numberBetween(0, 3),
                    'number_contact_person' => $faker->phoneNumber,
                    'ktp_photos' => $faker->imageUrl(),
                ]
            );
        }

        // ========================
        // CUSTOMERS
        // ========================
        $customers = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['name' => 'Bob Wilson', 'email' => 'bob@example.com'],
            ['name' => 'Alice Brown', 'email' => 'alice@example.com'],
            ['name' => 'Charlie Davis', 'email' => 'charlie@example.com'],
            ['name' => 'Diana Miller', 'email' => 'diana@example.com'],
            ['name' => 'Edward Garcia', 'email' => 'edward@example.com'],
            ['name' => 'Fiona Martinez', 'email' => 'fiona@example.com'],
            ['name' => 'George Anderson', 'email' => 'george@example.com'],
            ['name' => 'Hannah Taylor', 'email' => 'hannah@example.com'],
        ];

        foreach ($customers as $customer) {
            $user = User::firstOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'password' => Hash::make('password'),
                    'gender' => $faker->randomElement(['male', 'female']),
                    'phone' => $faker->phoneNumber,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'is_verified' => true,
                    'is_2fa_enabled' => $faker->boolean(30), // 30% aktif 2FA
                ]
            );

            $user->assignRole('customer');

            // 🔥 USER DETAIL
            UserDetail::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $faker->unique()->numerify('################'),
                    'birth_date' => $faker->date(),
                    'job' => $faker->jobTitle,
                    'office_name' => $faker->company,
                    'positions' => $faker->jobTitle,
                    'salary' => $faker->numberBetween(3000000, 10000000),
                    'marital' => $faker->randomElement(['single', 'married']),
                    'contact_person' => $faker->name,
                    'name_person' => $faker->name,
                    'kids' => $faker->numberBetween(0, 4),
                    'number_contact_person' => $faker->phoneNumber,
                    'ktp_photos' => $faker->imageUrl(),
                ]
            );

            // 🔥 WALLET
            Wallet::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => $faker->randomElement([
                        0, 50000, 100000, 250000, 500000, 750000, 1000000
                    ])
                ]
            );
        }
    }
}