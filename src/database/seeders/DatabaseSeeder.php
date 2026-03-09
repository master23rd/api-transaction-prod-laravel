<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call([
            RoleSeeder::class,
        ]);

        // Create cafe manager user
        $manager = User::firstOrCreate(
            ['email' => 'manager@koperasi.com'],
            [
                'name' => 'Koperasi Manager',
                'password' => Hash::make('password'),
            ]
        );
        $manager->assignRole('cafe_manager');

        // Create customer user
        $customer = User::firstOrCreate(
            ['email' => 'customer@koperasi.com'],
            [
                'name' => 'Customer',
                'password' => Hash::make('password'),
            ]
        );
        $customer->assignRole('customer');

        // Seed all other tables
        $this->call([
            // UserSeeder::class,          // Additional managers and customers
            CategorySeeder::class,      // Product categories
            CitySeeder::class,          // Cities
            ProductSeeder::class,       // Products (depends on Category)
            ProductOptionSeeder::class, // Product options (depends on Product)
            CafeSeeder::class,          // Cafes (depends on City)
            CafeTimeSlotSeeder::class,  // Cafe time slots (depends on Cafe)
            WalletSeeder::class,        // User wallets (depends on User)
            TransactionSeeder::class,   // Transactions (depends on User, Cafe)
            TransactionDetailSeeder::class, // Transaction details (depends on Transaction, Product)
            WalletTransactionSeeder::class, // Wallet transactions (depends on Wallet, Transaction)
        ]);
    }
}
