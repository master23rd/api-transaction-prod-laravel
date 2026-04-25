<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'balance',
        'account_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // direct relation ke branch (many-to-many)
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // akses store via transaksi
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'transactions')
            ->withTimestamps();
    }

    //relations to store 
    // public function stores()
    // {
    //     return $this->belongsToMany(Store::class, 'store_wallet'); 
    // }

    public function setAccountNumberAttribute($value)
    {
        if ($this->exists) {
            return; // ignore kalau sudah ada
        }

        $this->attributes['account_number'] = $value;
    }
}
