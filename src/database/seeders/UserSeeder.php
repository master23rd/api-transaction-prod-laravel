<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create additional cafe managers
        $managers = [
            ['name' => 'Ahmad Rizki', 'email' => 'ahmad@awake.com'],
            ['name' => 'Dewi Sartika', 'email' => 'dewi@awake.com'],
            ['name' => 'Budi Santoso', 'email' => 'budi@awake.com'],
            ['name' => 'Citra Dewi', 'email' => 'citra@awake.com'],
            ['name' => 'Eko Prasetyo', 'email' => 'eko@awake.com'],
        ];

        foreach ($managers as $manager) {
            $user = User::firstOrCreate(
                ['email' => $manager['email']],
                [
                    'name' => $manager['name'],
                    'password' => Hash::make('password'),
                    'phone' => fake()->phoneNumber(),
                ]
            );
            $user->assignRole('cafe_manager');
        }

        // Create additional customers
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
                    'gender' => fake()->randomElement(['male', 'female']),
                    'phone' => fake()->phoneNumber(),
                    'email_verified_at' => now(), // Set email as verified for testing
                ]
            );
            $user->assignRole('customer');

            // Create wallet for customer with random balance for testing
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => fake()->randomElement([0, 50000, 100000, 250000, 500000, 750000, 1000000])]
            );
        }
    }
}
