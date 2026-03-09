<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetailOption extends Model
{
    protected $fillable = [
        'transaction_detail_id',
        'product_option_id',
        'price',
    ];

    public function transactionDetail(): BelongsTo
    {
        return $this->belongsTo(TransactionDetail::class);
    }

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }
}
