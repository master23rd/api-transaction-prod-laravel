<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create wallets for all users who have customer role
        $customers = User::role('customer')->get();

        foreach ($customers as $customer) {
            Wallet::firstOrCreate(
                ['user_id' => $customer->id],
                [
                    'balance' => fake()->randomElement([0, 50000, 100000, 150000, 200000, 500000]),
                ]
            );
        }
    }
}
