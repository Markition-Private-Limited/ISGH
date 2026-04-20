<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDependent extends Model
{
    protected $fillable = [
        'member_id',
        'type',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'dob',
        'tx_dl',
        'gender',
        'relation',
        'street',
        'city',
        'state',
        'zip',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
