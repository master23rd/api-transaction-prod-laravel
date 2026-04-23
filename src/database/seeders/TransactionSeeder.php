<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::role('customer')->take(5)->get();
        $cafes = Cafe::all();

        if ($customers->isEmpty() || $cafes->isEmpty()) {
            return;
        }

        $paymentStatuses = ['pending', 'paid', 'failed', 'refunded'];
        $paymentMethods = ['wallet', 'bank_transfer', 'qris', 'cash'];

        // Create 10+ transactions
        for ($i = 0; $i < 12; $i++) {
            $customer = $customers->random();
            $cafe = $cafes->random();

            $paymentStatus = fake()->randomElement($paymentStatuses);
            $orderStatus = $paymentStatus === 'paid'
                ? fake()->randomElement(['pending', 'preparing', 'finished'])
                : ($paymentStatus === 'pending' ? 'pending' : 'cancelled');

            $discount = fake()->randomElement([0, 5000, 10000, 15000]);

            // Create transaction with placeholder values
            // Actual totals will be calculated in TransactionDetailSeeder
            Transaction::create([
                'user_id' => $customer->id,
                'cafe_id' => $cafe->id,
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'grand_total_amount' => 0, // Will be updated by TransactionDetailSeeder
                'total_tax_amount' => 0, // Will be updated by TransactionDetailSeeder
                'service_fee_amount' => 2000,
                'discount' => $discount,
                'tax_percentage_amount' => 11,
                'total_items' => 0, // Will be updated by TransactionDetailSeeder
                'proof_of_payment' => null,
                'payment_method' => fake()->randomElement($paymentMethods),
                'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
            ]);
        }
    }
}
