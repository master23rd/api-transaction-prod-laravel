<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'payment_status',
        'order_status',
        'grand_total_amount',
        'total_tax_amount',
        'service_fee_amount',
        'discount',
        'tax_percentage_amount',
        'total_items',
        'proof_of_payment',
        'cafe_id',
        'payment_method',
    ];

    protected function proofOfPaymentUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['proof_of_payment'] ? Storage::url($this->attributes['proof_of_payment']) : null,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function walletTransaction(): HasOne
    {
        return $this->hasOne(WalletTransaction::class);
    }
}
