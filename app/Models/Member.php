<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    protected $fillable = [
        'membership_type',
        'status',
        'membership_start_date',
        'membership_end_date',

        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'dob',
        'tx_dl',
        'gender',

        'street',
        'city',
        'state',
        'zip',
        'zone',

        'stripe_customer_id',
        'stripe_payment_method_id',
        'stripe_payment_intent_id',
        'stripe_subscription_id',
        'payment_status',
        'amount_cents',
        'checkomatic_monthly_cents',

        'terms_agreed',
        'auto_renewal',
        'terms_agreed_at',
    ];

    protected $casts = [
        'dob'                  => 'date',
        'membership_start_date'=> 'date',
        'membership_end_date'  => 'date',
        'terms_agreed'         => 'boolean',
        'auto_renewal'         => 'boolean',
        'terms_agreed_at'      => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function dependents(): HasMany
    {
        return $this->hasMany(MemberDependent::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function spouses(): HasMany
    {
        return $this->hasMany(MemberDependent::class)->where('type', 'spouse');
    }

    public function flatMembers(): HasMany
    {
        return $this->hasMany(MemberDependent::class)->where('type', 'flat_member');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function isLifetime(): bool
    {
        return str_starts_with($this->membership_type, 'lifetime');
    }

    public function isCheckomatic(): bool
    {
        return str_starts_with($this->membership_type, 'checkomatic');
    }

    public function isAnnual(): bool
    {
        return in_array($this->membership_type, ['family', 'individual', 'flat']);
    }
}
