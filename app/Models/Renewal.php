<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Renewal extends Model
{
    protected $guarded = [];

    protected $casts = [
        'processed'    => 'boolean',
        'amount_cents' => 'integer',
        'retry_count'  => 'integer',
        'wa_invoice_id'=> 'integer',
        'contact_id'   => 'integer',
        'paid_at'      => 'datetime',
    ];
}
