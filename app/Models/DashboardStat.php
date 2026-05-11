<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'total_members',
        'active_members',
        'lapsed_members',
        'individual_members',
        'checkmatic_members',
        'lifetime_members',
        'total_zips',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::first() ?? new static();
    }
}
