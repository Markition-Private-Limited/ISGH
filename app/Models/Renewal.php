<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Renewal extends Model
{
    protected $fillable = [
        'contact_id',
        'member_email',
        'membership_type',
        'zone',
        'amount_cents',
        'currency',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'status',
        'wa_invoice_id',
        'wa_step',
        'processed',
        'retry_count',
        'error_type',
        'error_code',
        'error_decline_code',
        'error_message',
        'payment_method',
        'card_brand',
        'card_last4',
        'paid_at',
    ];

    protected $casts = [
        'processed'    => 'boolean',
        'amount_cents' => 'integer',
        'retry_count'  => 'integer',
        'wa_invoice_id'=> 'integer',
        'contact_id'   => 'integer',
        'paid_at'      => 'datetime',
    ];
}
