<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'amount',
        'total_amount',
        'type',
        'status',
        'transaction_id',
        'proof_of_payment',
        'service_fee',
        'unique_code',
    ];

    protected $casts = [
        'wallet_id' => 'integer',
        'amount' => 'integer',
        'total_amount' => 'integer',
        'transaction_id' => 'integer',
        'service_fee' => 'integer',
        'unique_code' => 'integer',
    ];

    protected function proofOfPaymentUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['proof_of_payment'] ? Storage::url($this->attributes['proof_of_payment']) : null,
        );
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
