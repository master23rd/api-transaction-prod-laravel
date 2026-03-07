<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded,cancelled',
            'order_status' => 'nullable|string|in:pending,preparing,finished,cancelled',
            'cafe_id' => 'nullable|integer|exists:cafes,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $filters = $request->only(['payment_status', 'order_status', 'cafe_id', 'date_from', 'date_to']);
        $perPage = $request->input('per_page', 10);

        $transactions = $this->transactionService->getUserTransactions(
            $request->user()->id,
            $filters,
            $perPage
        );

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->map(fn ($t) => $this->transactionService->formatTransactionListItem($t)),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $transaction = $this->transactionService->getTransaction($id, $request->user()->id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transactionService->formatTransactionResponse($transaction),
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $transactions = $this->transactionService->getActiveTransactions($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->map(fn ($t) => $this->transactionService->formatTransactionListItem($t)),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cafe_id' => 'required|integer|exists:cafes,id',
            'payment_method' => 'required|string|in:wallet',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.options' => 'nullable|array',
            'items.*.options.*' => 'integer|exists:product_options,id',
        ]);

        try {
            $transaction = $this->transactionService->createTransaction(
                $request->user(),
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $this->transactionService->formatTransactionResponse($transaction),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reorder(Request $request, int $id): JsonResponse
    {
        $transaction = $this->transactionService->getTransaction($id, $request->user()->id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        try {
            $newTransaction = $this->transactionService->reorderTransaction(
                $request->user(),
                $transaction
            );

            return response()->json([
                'success' => true,
                'message' => 'Reorder successful',
                'data' => $this->transactionService->formatTransactionResponse($newTransaction),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $transaction = $this->transactionService->getTransaction($id, $request->user()->id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        try {
            $transaction = $this->transactionService->cancelTransaction($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Transaction cancelled successfully',
                'data' => $this->transactionService->formatTransactionResponse($transaction),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
