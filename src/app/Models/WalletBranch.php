<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletBranch extends Model
{
    protected $table = 'wallet_branch';
    protected $guarded = [];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}