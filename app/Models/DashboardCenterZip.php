<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardCenterZip extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dashboard_center_id',
        'zip',
        'city',
        'member_count',
    ];

    public function center()
    {
        return $this->belongsTo(DashboardCenter::class, 'dashboard_center_id');
    }
}
