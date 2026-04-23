<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionDetailOption;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Repositories\TransactionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionService
{
    protected float $taxPercentage = 10;
    protected float $serviceFee = 2000;

    public function __construct(
        protected TransactionRepository $transactionRepository
    ) {}

    public function getUserTransactions(int $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->transactionRepository->getByUser($userId, $filters, $perPage);
    }

    public function getTransaction(int $id, int $userId): ?Transaction
    {
        return $this->transactionRepository->findByIdAndUser($id, $userId);
    }

    public function getActiveTransactions(int $userId): Collection
    {
        return $this->transactionRepository->getActiveByUser($userId);
    }

    public function createTransaction(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            // Calculate totals
            $calculatedData = $this->calculateTransactionTotals($data['items']);

            // Create transaction
            $transaction = $this->transactionRepository->create([
                'user_id' => $user->id,
                'cafe_id' => $data['cafe_id'],
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'total_items' => $calculatedData['total_items'],
                'grand_total_amount' => $calculatedData['grand_total'],
                'total_tax_amount' => $calculatedData['tax_amount'],
                'service_fee_amount' => $this->serviceFee,
                'tax_percentage_amount' => $this->taxPercentage,
                'discount' => $data['discount'] ?? 0,
            ]);

            // Create transaction details
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $optionsTotal = $this->calculateOptionsTotal($item['options'] ?? []);
                $itemPrice = $product->price + $optionsTotal;
                $totalAmount = $itemPrice * $item['quantity'];

                $detail = TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'price' => $itemPrice,
                    'quantity' => $item['quantity'],
                    'total_amount' => $totalAmount,
                ]);

                // Create transaction detail options
                if (!empty($item['options'])) {
                    foreach ($item['options'] as $optionId) {
                        $option = ProductOption::find($optionId);
                        if ($option) {
                            TransactionDetailOption::create([
                                'transaction_detail_id' => $detail->id,
                                'product_option_id' => $optionId,
                                'price' => $option->price,
                            ]);
                        }
                    }
                }
            }

            // Process wallet payment if selected
            if ($data['payment_method'] === 'wallet') {
                $this->processWalletPayment($user, $transaction);
            }

            return $transaction->fresh(['cafe', 'details.product', 'details.options.productOption']);
        });
    }

    protected function calculateTransactionTotals(array $items): array
    {
        $subtotal = 0;
        $totalItems = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $optionsTotal = $this->calculateOptionsTotal($item['options'] ?? []);
            $itemPrice = $product->price + $optionsTotal;
            $subtotal += $itemPrice * $item['quantity'];
            $totalItems += $item['quantity'];
        }

        $taxAmount = $subtotal * ($this->taxPercentage / 100);
        $grandTotal = $subtotal + $taxAmount + $this->serviceFee;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'total_items' => $totalItems,
        ];
    }

    protected function calculateOptionsTotal(array $optionIds): float
    {
        if (empty($optionIds)) {
            return 0;
        }

        return ProductOption::whereIn('id', $optionIds)->sum('price');
    }

    protected function processWalletPayment(User $user, Transaction $transaction): void
    {
        $wallet = $user->wallet;

        if (!$wallet || $wallet->balance < $transaction->grand_total_amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        // Deduct from wallet
        $wallet->decrement('balance', $transaction->grand_total_amount);

        // Create wallet transaction record
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_id' => $transaction->id,
            'amount' => $transaction->grand_total_amount,
            'total_amount' => $transaction->grand_total_amount,
            'type' => 'payment',
            'status' => 'approved',
            'service_fee' => $this->serviceFee,
        ]);

        // Update transaction payment status
        $transaction->update(['payment_status' => 'paid']);
    }

    public function reorderTransaction(User $user, Transaction $originalTransaction): Transaction
    {
        // Build items array from original transaction
        $items = [];

        foreach ($originalTransaction->details as $detail) {
            // Check if product still exists
            $product = Product::find($detail->product_id);
            if (!$product) {
                throw new \Exception("Product '{$detail->product?->name}' is no longer available");
            }

            $item = [
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'options' => [],
            ];

            // Add options if any
            if ($detail->options && $detail->options->count() > 0) {
                foreach ($detail->options as $option) {
                    // Check if option still exists
                    $productOption = ProductOption::find($option->product_option_id);
                    if ($productOption) {
                        $item['options'][] = $productOption->id;
                    }
                }
            }

            $items[] = $item;
        }

        // Create new transaction with same cafe and items
        $data = [
            'cafe_id' => $originalTransaction->cafe_id,
            'payment_method' => 'wallet',
            'items' => $items,
        ];

        return $this->createTransaction($user, $data);
    }

    public function cancelTransaction(Transaction $transaction): Transaction
    {
        if (!in_array($transaction->order_status, ['pending'])) {
            throw new \Exception('Transaction cannot be cancelled');
        }

        // Refund wallet if paid via wallet
        if ($transaction->payment_status === 'paid' && $transaction->payment_method === 'wallet') {
            $walletTransaction = $transaction->walletTransaction;
            if ($walletTransaction) {
                $wallet = $walletTransaction->wallet;
                $wallet->increment('balance', $transaction->grand_total_amount);

                // Create refund wallet transaction
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->grand_total_amount,
                    'total_amount' => $transaction->grand_total_amount,
                    'type' => 'refund',
                    'status' => 'approved',
                    'service_fee' => 0,
                ]);
            }
        }

        return $this->transactionRepository->update($transaction, [
            'order_status' => 'cancelled',
            'payment_status' => $transaction->payment_status === 'paid' ? 'refunded' : 'cancelled',
        ]);
    }

    public function formatTransactionListItem(Transaction $transaction): array
    {
        // Calculate estimated time from product service_time * quantity
        $estimatedTime = $this->calculateEstimatedTime($transaction);

        return [
            'id' => $transaction->id,
            'cafe' => $transaction->cafe ? [
                'id' => $transaction->cafe->id,
                'name' => $transaction->cafe->name,
            ] : null,
            'payment_status' => $transaction->payment_status,
            'order_status' => $transaction->order_status,
            'payment_method' => $transaction->payment_method,
            'total_items' => $transaction->total_items,
            'grand_total_amount' => $transaction->grand_total_amount,
            'grand_total_formatted' => 'Rp ' . number_format($transaction->grand_total_amount, 0, ',', '.'),
            'estimated_time' => $estimatedTime,
            'created_at' => $transaction->created_at?->toISOString(),
        ];
    }

    protected function calculateEstimatedTime(Transaction $transaction): int
    {
        if (!$transaction->details) {
            return 0;
        }

        $totalTime = 0;
        foreach ($transaction->details as $detail) {
            if ($detail->product) {
                $totalTime += ($detail->product->service_time ?? 0) * $detail->quantity;
            }
        }

        return $totalTime;
    }

    public function formatTransactionResponse(Transaction $transaction): array
    {
        // Calculate estimated time from product service_time * quantity
        $estimatedTime = $this->calculateEstimatedTime($transaction);

        return [
            'id' => $transaction->id,
            'cafe' => $transaction->cafe ? [
                'id' => $transaction->cafe->id,
                'name' => $transaction->cafe->name,
                'slug' => $transaction->cafe->slug,
            ] : null,
            'payment_status' => $transaction->payment_status,
            'order_status' => $transaction->order_status,
            'payment_method' => $transaction->payment_method,
            'total_items' => $transaction->total_items,
            'subtotal' => $transaction->grand_total_amount - $transaction->total_tax_amount - $transaction->service_fee_amount,
            'subtotal_formatted' => 'Rp ' . number_format($transaction->grand_total_amount - $transaction->total_tax_amount - $transaction->service_fee_amount, 0, ',', '.'),
            'tax_percentage' => $transaction->tax_percentage_amount,
            'tax_amount' => $transaction->total_tax_amount,
            'tax_amount_formatted' => 'Rp ' . number_format($transaction->total_tax_amount, 0, ',', '.'),
            'service_fee' => $transaction->service_fee_amount,
            'service_fee_formatted' => 'Rp ' . number_format($transaction->service_fee_amount, 0, ',', '.'),
            'discount' => $transaction->discount,
            'discount_formatted' => 'Rp ' . number_format($transaction->discount, 0, ',', '.'),
            'grand_total_amount' => $transaction->grand_total_amount,
            'grand_total_formatted' => 'Rp ' . number_format($transaction->grand_total_amount, 0, ',', '.'),
            'estimated_time' => $estimatedTime,
            'proof_of_payment' => $transaction->proof_of_payment,
            'proof_of_payment_url' => $transaction->proof_of_payment ? url(Storage::url($transaction->proof_of_payment)) : null,
            'items' => $this->formatTransactionDetails($transaction->details),
            'created_at' => $transaction->created_at?->toISOString(),
            'updated_at' => $transaction->updated_at?->toISOString(),
        ];
    }

    protected function formatTransactionDetails($details): array
    {
        if (!$details) {
            return [];
        }

        return $details->map(function ($detail) {
            return [
                'id' => $detail->id,
                'product' => $detail->product ? [
                    'id' => $detail->product->id,
                    'name' => $detail->product->name,
                    'category' => $detail->product->category?->name,
                ] : null,
                'price' => $detail->price,
                'price_formatted' => 'Rp ' . number_format($detail->price, 0, ',', '.'),
                'quantity' => $detail->quantity,
                'total_amount' => $detail->total_amount,
                'total_amount_formatted' => 'Rp ' . number_format($detail->total_amount, 0, ',', '.'),
                'options' => $this->formatDetailOptions($detail->options),
            ];
        })->toArray();
    }

    protected function formatDetailOptions($options): array
    {
        if (!$options) {
            return [];
        }

        return $options->map(function ($option) {
            return [
                'id' => $option->id,
                'name' => $option->productOption?->name,
                'type' => $option->productOption?->type,
                'price' => $option->price,
                'price_formatted' => 'Rp ' . number_format($option->price, 0, ',', '.'),
            ];
        })->toArray();
    }
}
