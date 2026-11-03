<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberOptIn extends Model
{
    public const STATUS_WAITING = 'waiting_for_approval';

    protected $fillable = [
        'contact_id',
        'member_email',
        'first_name',
        'last_name',
        'membership_type',
        'center',
        'consent_given',
        'consent_at',
        'ip_address',
        'status',
    ];

    protected $casts = [
        'contact_id'    => 'integer',
        'consent_given' => 'boolean',
        'consent_at'    => 'datetime',
    ];

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function emailExists(string $email): bool
    {
        return static::where('member_email', static::normalizeEmail($email))->exists();
    }
}
