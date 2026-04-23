<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionDetailOption;
use Illuminate\Database\Seeder;

class TransactionDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactions = Transaction::all();
        $products = Product::all();

        if ($transactions->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($transactions as $transaction) {
            // Create 1-3 details per transaction
            $detailCount = fake()->numberBetween(1, 3);
            $usedProducts = [];
            $transactionSubtotal = 0;
            $totalItems = 0;

            for ($i = 0; $i < $detailCount; $i++) {
                // Get a random product that hasn't been used in this transaction
                $availableProducts = $products->whereNotIn('id', $usedProducts);
                if ($availableProducts->isEmpty()) {
                    break;
                }

                $product = $availableProducts->random();
                $usedProducts[] = $product->id;

                $quantity = fake()->numberBetween(1, 3);
                $basePrice = $product->price;
                $optionPrice = 0;

                // Check if product is a beverage (has options)
                $isBeverage = !in_array($product->category?->slug, ['snack', 'merchandise']);

                $detail = TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'price' => $basePrice, // Will be updated after options
                    'quantity' => $quantity,
                    'total_amount' => $basePrice * $quantity, // Will be updated after options
                ]);

                // Add options for beverages
                if ($isBeverage) {
                    $productOptions = ProductOption::where('product_id', $product->id)->get();

                    // Group options by type
                    $optionsByType = $productOptions->groupBy('type');

                    foreach (['size', 'dairy', 'sweetness', 'ice'] as $type) {
                        if (isset($optionsByType[$type]) && $optionsByType[$type]->isNotEmpty()) {
                            // Randomly select one option of this type
                            $selectedOption = $optionsByType[$type]->random();

                            TransactionDetailOption::create([
                                'transaction_detail_id' => $detail->id,
                                'product_option_id' => $selectedOption->id,
                                'price' => $selectedOption->price,
                            ]);

                            $optionPrice += $selectedOption->price;
                        }
                    }

                    // Update detail with option prices
                    $totalPrice = $basePrice + $optionPrice;
                    $detail->update([
                        'price' => $totalPrice,
                        'total_amount' => $totalPrice * $quantity,
                    ]);
                }

                $transactionSubtotal += $detail->total_amount;
                $totalItems += $quantity;
            }

            // Update transaction totals based on actual details
            $taxPercentage = $transaction->tax_percentage_amount ?? 11;
            $taxAmount = (int) ($transactionSubtotal * $taxPercentage / 100);
            $serviceFee = $transaction->service_fee_amount ?? 2000;
            $discount = $transaction->discount ?? 0;
            $grandTotal = $transactionSubtotal + $taxAmount + $serviceFee - $discount;

            $transaction->update([
                'total_items' => $totalItems,
                'total_tax_amount' => $taxAmount,
                'grand_total_amount' => $grandTotal,
            ]);
        }
    }
}
