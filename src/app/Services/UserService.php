<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\UserDetail;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    public function registerCustomer(array $data): User
    {
        DB::beginTransaction();

        try {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
            ];

            // Handle photo upload
            if (isset($data['photo'])) {
                $userData['photo'] = $data['photo']->store('profile-photos', 'public');
            }

            // Create user
            $user = $this->userRepository->create($userData);

            // Assign role
            $user->assignRole('customer');

            // Create wallet
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            // INSERT USER DETAIL (BARU)
            UserDetail::create([
                'user_id' => $user->id,
                'nik' => $data['nik'],
                'birth_date' => $data['birth_date'],
                'job' => $data['job'] ?? null,
                'office_name' => $data['office_name'] ?? null,
                'positions' => $data['positions'] ?? null,
                'salary' => $data['salary'] ?? null,
                'marital' => $data['marital'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'name_person' => $data['name_person'] ?? null,
                'kids' => $data['kids'] ?? 0,
                'number_contact_person' => $data['number_contact_person'] ?? null,
                'ktp_photos' => $data['ktp_photos'] ?? null,
            ]);

            DB::commit();

            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function findById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->userRepository->findByPhone($phone);
    }

    public function updateProfile(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'gender' => $data['gender'] ?? $user->gender,
        ];

        return $this->userRepository->update($user, $updateData);
    }

    public function updateUserProfile(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'gender' => $data['gender'] ?? $user->gender,
        ];

        // Handle photo upload
        if (isset($data['photo'])) {
            // Delete old photo if exists
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $updateData['photo'] = $data['photo']->store('profile-photos', 'public');
        }

        // Handle photo removal
        if (isset($data['remove_photo']) && $data['remove_photo']) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $updateData['photo'] = null;
        }

        return $this->userRepository->update($user, $updateData);
    }

    public function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'photo' => $user->photo,
            'photo_url' => $user->photo_url,
            'email_verified_at' => $user->email_verified_at,
            'roles' => $user->getRoleNames(),
            'wallet_balance' => $user->wallet?->balance ?? 0,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    public function getUserStats(User $user): array
    {
        $stats = Transaction::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->select([
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('COALESCE(SUM(grand_total_amount), 0) as total_spent'),
                DB::raw('COALESCE(SUM(total_items), 0) as total_items'),
            ])
            ->first();

        $monthlyStats = Transaction::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->select([
                DB::raw('COUNT(*) as transactions_this_month'),
                DB::raw('COALESCE(SUM(grand_total_amount), 0) as spent_this_month'),
            ])
            ->first();

        $favoriteProduct = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.payment_status', 'paid')
            ->select('products.id', 'products.name', DB::raw('SUM(transaction_details.quantity) as total_ordered'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_ordered')
            ->first();

        $favoriteCafe = DB::table('transactions')
            ->join('cafes', 'cafes.id', '=', 'transactions.cafe_id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.payment_status', 'paid')
            ->select('cafes.id', 'cafes.name', DB::raw('COUNT(*) as total_visits'))
            ->groupBy('cafes.id', 'cafes.name')
            ->orderByDesc('total_visits')
            ->first();

        return [
            'total_transactions' => (int) $stats->total_transactions,
            'total_spent' => (int) $stats->total_spent,
            'total_spent_formatted' => 'Rp ' . number_format($stats->total_spent, 0, ',', '.'),
            'total_items' => (int) $stats->total_items,
            'transactions_this_month' => (int) $monthlyStats->transactions_this_month,
            'spent_this_month' => (int) $monthlyStats->spent_this_month,
            'spent_this_month_formatted' => 'Rp ' . number_format($monthlyStats->spent_this_month, 0, ',', '.'),
            'average_per_transaction' => $stats->total_transactions > 0
                ? (int) ($stats->total_spent / $stats->total_transactions)
                : 0,
            'average_per_transaction_formatted' => $stats->total_transactions > 0
                ? 'Rp ' . number_format($stats->total_spent / $stats->total_transactions, 0, ',', '.')
                : 'Rp 0',
            'favorite_product' => $favoriteProduct ? [
                'id' => $favoriteProduct->id,
                'name' => $favoriteProduct->name,
                'total_ordered' => (int) $favoriteProduct->total_ordered,
            ] : null,
            'favorite_cafe' => $favoriteCafe ? [
                'id' => $favoriteCafe->id,
                'name' => $favoriteCafe->name,
                'total_visits' => (int) $favoriteCafe->total_visits,
            ] : null,
            'member_since' => $user->created_at->toISOString(),
            'member_days' => $user->created_at->diffInDays(now()),
        ];
    }
}
