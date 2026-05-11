<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardCenter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'zone_name',
        'center_name',
        'member_count',
        'active_members',
        'lapsed_members',
        'individual_members',
        'checkmatic_members',
        'lifetime_members',
    ];

    public function zips()
    {
        return $this->hasMany(DashboardCenterZip::class);
    }
}
