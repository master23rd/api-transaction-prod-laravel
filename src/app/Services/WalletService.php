<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Repositories\WalletRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class WalletService
{
    protected float $topupServiceFee = 1000;
    protected int $maxUniqueCode = 200;

    public function __construct(
        protected WalletRepository $walletRepository
    ) {}

    public function getWallet(User $user): ?Wallet
    {
        return $this->walletRepository->findByUserId($user->id);
    }

    public function getTransactions(int $walletId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->walletRepository->getTransactions($walletId, $filters, $perPage);
    }

    public function getTransaction(int $id, int $walletId): ?WalletTransaction
    {
        return $this->walletRepository->findTransactionByIdAndWallet($id, $walletId);
    }

    public function requestTopup(User $user, array $data): WalletTransaction
    {
        $wallet = $this->getWallet($user);

        if (!$wallet) {
            throw new \Exception('Wallet not found');
        }

        // Use unique code from request (validated to be between 1-200)
        $uniqueCode = $data['unique_code'];

        // Validate unique code is within range
        if ($uniqueCode < 1 || $uniqueCode > $this->maxUniqueCode) {
            throw new \Exception('Unique code must be between 1 and ' . $this->maxUniqueCode);
        }

        $amount = $data['amount']; // Pure top-up amount
        $totalAmount = $amount + $this->topupServiceFee - $uniqueCode;

        $transactionData = [
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'total_amount' => $totalAmount,
            'type' => 'topup',
            'status' => 'pending',
            'service_fee' => $this->topupServiceFee,
            'unique_code' => $uniqueCode,
        ];

        // Handle proof of payment upload
        if (isset($data['proof_of_payment'])) {
            $transactionData['proof_of_payment'] = $data['proof_of_payment']->store('wallet-proofs', 'public');
        }

        return $this->walletRepository->createTransaction($transactionData);
    }

    public function uploadProofOfPayment(WalletTransaction $transaction, $file): WalletTransaction
    {
        if ($transaction->status !== 'pending') {
            throw new \Exception('Cannot upload proof for non-pending transaction');
        }

        // Delete old proof if exists
        if ($transaction->proof_of_payment) {
            Storage::disk('public')->delete($transaction->proof_of_payment);
        }

        $path = $file->store('wallet-proofs', 'public');

        return $this->walletRepository->updateTransaction($transaction, [
            'proof_of_payment' => $path,
        ]);
    }

    public function cancelTopup(WalletTransaction $transaction): WalletTransaction
    {
        if ($transaction->status !== 'pending') {
            throw new \Exception('Only pending topup can be cancelled');
        }

        if ($transaction->type !== 'topup') {
            throw new \Exception('Only topup transactions can be cancelled');
        }

        return $this->walletRepository->updateTransaction($transaction, [
            'status' => 'cancelled',
        ]);
    }

    public function formatWalletResponse(Wallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'balance' => $wallet->balance,
            'balance_formatted' => 'Rp ' . number_format($wallet->balance, 0, ',', '.'),
            'created_at' => $wallet->created_at?->toISOString(),
            'updated_at' => $wallet->updated_at?->toISOString(),
        ];
    }

    public function formatTransactionListItem(WalletTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'amount_formatted' => 'Rp ' . number_format($transaction->amount ?? 0, 0, ',', '.'),
            'total_amount' => $transaction->total_amount,
            'total_amount_formatted' => 'Rp ' . number_format($transaction->total_amount, 0, ',', '.'),
            'service_fee' => $transaction->service_fee,
            'unique_code' => $transaction->unique_code,
            'order_id' => $transaction->transaction_id, // Link to order/transaction
            'cafe' => $transaction->transaction?->cafe ? [
                'id' => $transaction->transaction->cafe->id,
                'name' => $transaction->transaction->cafe->name,
            ] : null,
            'created_at' => $transaction->created_at?->toISOString(),
        ];
    }

    public function formatTransactionResponse(WalletTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'amount_formatted' => 'Rp ' . number_format($transaction->amount ?? 0, 0, ',', '.'),
            'total_amount' => $transaction->total_amount,
            'total_amount_formatted' => 'Rp ' . number_format($transaction->total_amount, 0, ',', '.'),
            'service_fee' => $transaction->service_fee,
            'service_fee_formatted' => 'Rp ' . number_format($transaction->service_fee ?? 0, 0, ',', '.'),
            'unique_code' => $transaction->unique_code,
            'proof_of_payment' => $transaction->proof_of_payment,
            'proof_of_payment_url' => $transaction->proof_of_payment ? url(Storage::url($transaction->proof_of_payment)) : null,
            'transaction' => $transaction->transaction ? [
                'id' => $transaction->transaction->id,
                'cafe' => $transaction->transaction->cafe ? [
                    'id' => $transaction->transaction->cafe->id,
                    'name' => $transaction->transaction->cafe->name,
                ] : null,
            ] : null,
            'created_at' => $transaction->created_at?->toISOString(),
            'updated_at' => $transaction->updated_at?->toISOString(),
        ];
    }

    public function getTopupInfo(): array
    {
        return [
            'service_fee' => $this->topupServiceFee,
            'service_fee_formatted' => 'Rp ' . number_format($this->topupServiceFee, 0, ',', '.'),
            'max_unique_code' => $this->maxUniqueCode,
            'bank_accounts' => [
                [
                    'bank_name' => 'BCA',
                    'account_number' => '1234567890',
                    'account_name' => 'PT Awake Coffee Indonesia',
                ],
                [
                    'bank_name' => 'Mandiri',
                    'account_number' => '0987654321',
                    'account_name' => 'PT Awake Coffee Indonesia',
                ],
                [
                    'bank_name' => 'BNI',
                    'account_number' => '1122334455',
                    'account_name' => 'PT Awake Coffee Indonesia',
                ],
            ],
            'instructions' => [
                'Transfer sesuai nominal yang tertera (sudah termasuk potongan kode unik)',
                'Upload bukti transfer',
                'Tunggu verifikasi dari admin (maksimal 1x24 jam)',
                'Saldo akan otomatis bertambah setelah diverifikasi',
            ],
        ];
    }
}
