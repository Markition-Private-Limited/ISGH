<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync dashboard data from WildApricot to DB every hour.
Schedule::command('portal:sync-dashboard')->hourly()->withoutOverlapping(10);
