<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBranch extends Model
{
    protected $table = 'store_branch';
    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    //observer
    protected static function booted()
    {
        static::created(function ($storeBranch) {

            $branch = $storeBranch->branch;
            $store = $storeBranch->store;

            foreach ($branch->wallets as $wallet) {
                $wallet->stores()->syncWithoutDetaching([$store->id]);
            }
        });
    }
}
