<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'member_id',
        'ref',
        'membership_type',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'amount_cents',
        'currency',
        'status',
        'card_brand',
        'card_last4',
        'card_exp_month',
        'card_exp_year',
        'payment_method_type',
        'receipt_email',
        'description',
        'error_code',
        'error_type',
        'error_message',
        'error_decline_code',
        'customer_response',
        'payment_intent_response',
        'payment_confirm_response',
        'paid_at',
    ];

    protected $casts = [
        'customer_response'       => 'array',
        'payment_intent_response' => 'array',
        'payment_confirm_response'=> 'array',
        'paid_at'                 => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function amountFormatted(): string
    {
        return '$' . number_format($this->amount_cents / 100, 2);
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
