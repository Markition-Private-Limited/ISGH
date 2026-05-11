<?php

namespace App\Console\Commands;

use App\Services\WildApricotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmDashboardCache extends Command
{
    protected $signature   = 'portal:sync-dashboard';
    protected $description = 'Fetch WildApricot dashboard stats and persist them to the database (runs hourly via scheduler)';

    public function handle(WildApricotService $wa): int
    {
        set_time_limit(600);

        $this->info('Syncing dashboard data from WildApricot to DB...');

        $start = microtime(true);
        $wa->syncDashboardToDb();
        $elapsed = round(microtime(true) - $start, 1);

        // Bump member page cache version so stale paginated results are discarded
        Cache::increment('members_cache_version');

        $this->info("Done in {$elapsed}s. Dashboard DB updated, member caches invalidated.");
        return self::SUCCESS;
    }
}
