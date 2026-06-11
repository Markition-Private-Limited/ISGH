<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelChange extends Model
{
    protected $fillable = [
        'contact_id', 'member_email', 'from_type', 'to_type', 'zone', 'amount_cents',
        'currency', 'family_members', 'created_family_ids',
        'stripe_customer_id', 'stripe_payment_method_id', 'stripe_payment_intent_id',
        'stripe_charge_id', 'status', 'wa_invoice_id', 'wa_bundle_id', 'wa_level_id',
        'wa_step', 'processed', 'retry_count', 'error_type', 'error_code',
        'error_decline_code', 'error_message', 'payment_method', 'card_brand',
        'card_last4', 'paid_at',
    ];

    protected $casts = [
        'family_members'     => 'array',
        'created_family_ids' => 'array',
        'processed'          => 'boolean',
        'amount_cents'       => 'integer',
        'retry_count'        => 'integer',
        'contact_id'         => 'integer',
        'wa_invoice_id'      => 'integer',
        'wa_bundle_id'       => 'integer',
        'wa_level_id'        => 'integer',
        'paid_at'            => 'datetime',
    ];
}
