<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'stripe_intent_id',
        'member_id',
        'stripe_payment_method_id',
        'data',
        'processed',
        'wa_contact_id',
        'wa_invoice_id',
        'invoice_data',
        'payment_data',
        'processed_at',
        'wa_step',
        'wa_error',
        'wa_error_at',
        'retry_count',
        'stripe_paid',
    ];

    protected $casts = [
        'data'         => 'array',
        'invoice_data' => 'array',
        'payment_data' => 'array',
        'processed'    => 'boolean',
        'stripe_paid'  => 'boolean',
        'processed_at' => 'datetime',
        'wa_error_at'  => 'datetime',
    ];

    /** True if Stripe paid but WA failed or not yet run. */
    public function isFailed(): bool
    {
        return $this->stripe_paid && ! $this->processed;
    }

    public function statusLabel(): string
    {
        if ($this->processed)    return 'Completed';
        if ($this->wa_error)     return 'WA Failed';
        if ($this->stripe_paid)  return 'Pending WA';
        return 'Awaiting Payment';
    }

    public function statusColor(): string
    {
        return match($this->statusLabel()) {
            'Completed'       => 'green',
            'WA Failed'       => 'red',
            'Pending WA'      => 'orange',
            default           => 'gray',
        };
    }
}
