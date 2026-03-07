<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getWallet($request->user());

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->walletService->formatWalletResponse($wallet),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|in:topup,payment,refund',
            'status' => 'nullable|string|in:pending,approved,rejected,cancelled',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $wallet = $this->walletService->getWallet($request->user());

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $filters = $request->only(['type', 'status', 'date_from', 'date_to']);
        $perPage = $request->input('per_page', 10);

        $transactions = $this->walletService->getTransactions($wallet->id, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->map(fn ($t) => $this->walletService->formatTransactionListItem($t)),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ],
        ]);
    }

    public function showTransaction(Request $request, int $id): JsonResponse
    {
        $wallet = $this->walletService->getWallet($request->user());

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $transaction = $this->walletService->getTransaction($id, $wallet->id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->walletService->formatTransactionResponse($transaction),
        ]);
    }

    public function topupInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->walletService->getTopupInfo(),
        ]);
    }

    public function requestTopup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:10000',
            'unique_code' => 'required|integer|min:1|max:200',
            'proof_of_payment' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $transaction = $this->walletService->requestTopup($request->user(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Topup request created successfully',
                'data' => $this->walletService->formatTransactionResponse($transaction),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function uploadProof(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'proof_of_payment' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $wallet = $this->walletService->getWallet($request->user());

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $transaction = $this->walletService->getTransaction($id, $wallet->id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        try {
            $transaction = $this->walletService->uploadProofOfPayment(
                $transaction,
                $request->file('proof_of_payment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Proof of payment uploaded successfully',
                'data' => $this->walletService->formatTransactionResponse($transaction),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancelTopup(Request $request, int $id): JsonResponse
    {
        $wallet = $this->walletService->getWallet($request->user());

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $transaction = $this->walletService->getTransaction($id, $wallet->id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        try {
            $transaction = $this->walletService->cancelTopup($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Topup cancelled successfully',
                'data' => $this->walletService->formatTransactionResponse($transaction),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
