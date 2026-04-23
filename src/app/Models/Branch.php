<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    // wallet relation
    public function walletBranches()
    {
        return $this->hasMany(WalletBranch::class);
    }

    public function wallets()
    {
        return $this->belongsToMany(Wallet::class, 'wallet_branch');
    }

    // store relation
    public function storeBranches()
    {
        return $this->hasMany(StoreBranch::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_branch');
    }

    // time slots
    public function timeSlots()
    {
        return $this->hasMany(BranchTimeSlot::class);
    }
}
