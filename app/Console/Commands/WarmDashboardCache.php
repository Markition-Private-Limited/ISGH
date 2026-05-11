<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WildApricotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmDashboardCache extends Command
{
    protected $signature   = 'portal:warm-dashboard';
    protected $description = 'Pre-fetch WildApricot dashboard stats into cache (run via scheduler or manually)';

    public function handle(WildApricotService $wa): int
    {
        // This command pages through all WA member contacts to build ZIP aggregations.
        // With 6000+ members at 100/page it takes ~4-5 minutes — run via scheduler at 2am.
        set_time_limit(600);

        $this->info('Warming dashboard cache (this takes 4-5 minutes)...');

        Cache::forget('wa_dashboard_data');

        $start = microtime(true);
        $wa->getDashboardData();
        $elapsed = round(microtime(true) - $start, 1);

        // Bump the global members cache version — all per-user member page caches
        // include this version in their key, so incrementing it instantly invalidates them.
        Cache::increment('members_cache_version');

        $this->info("Done in {$elapsed}s. Member page caches invalidated.");
        return self::SUCCESS;
    }
}
