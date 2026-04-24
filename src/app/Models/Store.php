<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    // pivot
    public function storeBranches()
    {
        return $this->hasMany(StoreBranch::class);
    }

    // branch relation
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // products
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}