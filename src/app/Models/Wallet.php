<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // pivot
    public function walletBranches()
    {
        return $this->hasMany(WalletBranch::class);
    }

    // direct relation ke branch (many-to-many)
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'wallet_branch');
    }
}
