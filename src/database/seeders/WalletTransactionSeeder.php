<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;

class WalletTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = Wallet::all();

        if ($wallets->isEmpty()) {
            return;
        }

        $types = ['topup', 'payment', 'refund'];
        $statuses = ['pending', 'approved', 'rejected'];

        // Create wallet transactions for each wallet
        foreach ($wallets as $wallet) {
            // Create 2-4 transactions per wallet
            $transactionCount = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $transactionCount; $i++) {
                $type = fake()->randomElement($types);
                $status = fake()->randomElement($statuses);

                // Get a random transaction for payment type (if exists)
                $transactionId = null;
                if ($type === 'payment') {
                    $userTransaction = Transaction::where('user_id', $wallet->user_id)
                        ->where('payment_method', 'wallet')
                        ->inRandomOrder()
                        ->first();
                    $transactionId = $userTransaction?->id;
                }

                // Amount is the pure top-up/payment amount
                $amount = match ($type) {
                    'topup' => fake()->randomElement([50000, 100000, 200000, 500000]),
                    'payment' => fake()->numberBetween(25000, 200000),
                    'refund' => fake()->numberBetween(25000, 100000),
                };

                $serviceFee = $type === 'topup' ? 1000 : 0;
                $uniqueCode = $type === 'topup' ? fake()->numberBetween(1, 200) : null;

                // For topup: total_amount = amount + service_fee - unique_code
                // For payment/refund: total_amount = amount
                $totalAmount = $type === 'topup'
                    ? $amount + $serviceFee - ($uniqueCode ?? 0)
                    : $amount;

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'total_amount' => $totalAmount,
                    'type' => $type,
                    'status' => $status,
                    'transaction_id' => $transactionId,
                    'proof_of_payment' => null,
                    'service_fee' => $serviceFee,
                    'unique_code' => $uniqueCode,
                    'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);
            }
        }
    }
}
