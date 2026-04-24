<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletBranch extends Model
{
    protected $table = 'wallet_branch';
    protected $guarded = [];

    //observer
    protected static function booted()
    {
        static::created(function ($walletBranch) {

            $branch = $walletBranch->branch;
            $wallet = $walletBranch->wallet;

            // ambil semua store dari branch
            $stores = $branch->stores;

            foreach ($stores as $store) {
                // attach ke wallet (buat pivot baru kalau perlu)
                $wallet->stores()->syncWithoutDetaching([$store->id]);
            }
        });
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}