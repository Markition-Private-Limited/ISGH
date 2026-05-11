<?php

namespace App\Http\Controllers;

use App\Services\WildApricotService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PortalController extends Controller
{
    // ── Auth ──────────────────────────────────────────────────────────────

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                return back()
                    ->withInput(['email' => $request->input('email')])
                    ->withErrors(['email' => 'Your account has been deactivated.']);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('portal.dashboard'));
        }

        return back()
            ->withInput(['email' => $request->input('email')])
            ->withErrors(['email' => 'These credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('portal.login');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    public function dashboard()
    {
        $user = Auth::user();

        $data = app(\App\Services\WildApricotService::class)->getDashboardFromDb();

        if ($data === null) {
            $data = [
                'stats'          => ['total' => 0, 'active' => 0, 'lapsed' => 0],
                'levelBreakdown' => ['individual' => 0, 'checkmatic' => 0, 'lifetime' => 0],
                'profileStatus'  => ['active' => 0, 'lapsed' => 0, 'active_pct' => 0, 'lapsed_pct' => 0],
                'zipStats'       => ['total' => 0],
                'zipData'        => collect(),
                'zones'          => [],
                '_warming'       => true,
            ];
        } else {
            $data = $this->scopeDashboardData($data, $user);
        }

        return view('portal.dashboard', $data);
    }

    // ── Dashboard scoping ─────────────────────────────────────────────────

    // Maps DB center values to the WA label text used as center name in cached dashboard data.
    // WA center names are the full label after the zone prefix, e.g. "Masjid Bilal - Adel Road".
    private const CENTER_WA_LABELS = [
        'Adel Road'    => 'Masjid Bilal - Adel Road',
        'Champions'    => 'Masjid Al-Salam - Champions',
        'Woodlands'    => 'Masjid Al-Ansar - Woodlands',
        'Cypress'      => 'Cypress Islamic Center',
        'Bear Creek'   => 'Masjid Al-Mustafa - Bear Creek',
        'Katy'         => 'Masjid Aqsa - Katy',
        'Spring Branch'=> 'Spring Branch Islamic Center',
        'HWY3'         => 'Masjid Abubakr - HWY 3',
        'Pearland'     => 'Pearland Islamic Center',
        'Brand Lane'   => 'Masjid As-Sabireen - Brand Lane',
        'Ayesha'       => 'Masjid Ayesha',
        'River Oaks'   => 'River Oaks Islamic Center',
        'Synott'       => 'Masjid Attaqwa - Synott',
        'Mission Bend' => 'Masjid Hamza - Mission Bend',
        'New Territory'=> 'Masjid Maryam - New Territory',
    ];

    private function scopeDashboardData(array $data, \App\Models\User $user): array
    {
        if ($user->isCityWide()) {
            return $data; // sees everything
        }

        // Zone label in WA is e.g. "North Zone"; user->zone is e.g. "North"
        $userZoneLabel = $user->zone . ' Zone';

        // Keep only the user's zone
        $zones = array_values(array_filter(
            $data['zones'] ?? [],
            fn($z) => ($z['name'] ?? '') === $userZoneLabel
        ));

        if ($user->isCenterLevel()) {
            $waLabel = self::CENTER_WA_LABELS[$user->center] ?? $user->center;
            $zones   = array_map(function ($zone) use ($waLabel) {
                $zone['centers'] = array_values(array_filter(
                    $zone['centers'] ?? [],
                    fn($c) => strcasecmp($c['name'] ?? '', $waLabel) === 0
                ));
                $zone['members'] = array_sum(array_column($zone['centers'], 'total'));
                $zone['masjids'] = count($zone['centers']);
                return $zone;
            }, $zones);
        }

        // Collect all visible centers (applies to both zone and center level)
        $visibleCenters = [];
        foreach ($zones as $z) {
            foreach ($z['centers'] as $c) {
                $visibleCenters[] = $c;
            }
        }

        // Recompute all stats from visible centers
        $data['stats'] = [
            'total'  => array_sum(array_column($visibleCenters, 'total')),
            'active' => array_sum(array_column($visibleCenters, 'active')),
            'lapsed' => array_sum(array_column($visibleCenters, 'lapsed')),
        ];

        $data['levelBreakdown'] = [
            'individual' => array_sum(array_column($visibleCenters, 'individual')),
            'checkmatic' => array_sum(array_column($visibleCenters, 'checkmatic')),
            'lifetime'   => array_sum(array_column($visibleCenters, 'lifetime')),
        ];

        $scopedTotal     = $data['stats']['total'];
        $scopedActive    = $data['stats']['active'];
        $scopedActivePct = $scopedTotal > 0 ? (int) round($scopedActive / $scopedTotal * 100) : 0;

        $data['profileStatus'] = [
            'active'     => $scopedActive,
            'lapsed'     => $data['stats']['lapsed'],
            'active_pct' => $scopedActivePct,
            'lapsed_pct' => 100 - $scopedActivePct,
        ];

        // Narrow ZIP data to visible centers only
        $scopedZips = [];
        foreach ($visibleCenters as $c) {
            foreach ($c['zips'] ?? [] as $zipRow) {
                $zip = $zipRow['code'];
                $scopedZips[$zip] = ($scopedZips[$zip] ?? 0) + $zipRow['count'];
            }
        }
        $data['zipData'] = collect(array_map(
            fn($zip, $n) => ['zip' => $zip, 'city' => '', 'count' => $n],
            array_keys($scopedZips),
            array_values($scopedZips)
        ));

        $data['zones']             = $zones;
        $data['zipStats']['total'] = count($data['zipData']);

        return $data;
    }

    // ── Members ───────────────────────────────────────────────────────────

    public function members(Request $request, WildApricotService $wa)
    {
        $user    = Auth::user();
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 25;

        $filters = array_filter([
            'search' => $request->input('search', ''),
            'status' => $request->input('status', ''),
            'zone'   => $request->input('zone', ''),
            'center' => $request->input('center', ''),
            'zip'    => $request->input('zip', ''),
            'type'   => $request->input('type', ''),
        ]);

        // Always enforce zone/center from the user's authorised scope —
        // prevents URL manipulation to access another zone/center's data.
        if ($user->isZoneLevel()) {
            // Zone is always locked; center may be chosen from within their zone via the UI
            $filters['zone'] = $user->zone;
            // If a center filter was supplied, verify it belongs to this zone — else clear it
            if (!empty($filters['center'])) {
                $allowed = array_keys(self::CENTER_WA_LABELS);
                // Only keep the center filter if it's a real center name (not an injection attempt)
                // The OData map will scope it to the zone's choices automatically
                if (!in_array($filters['center'], $allowed, true)) {
                    unset($filters['center']);
                }
            }
        } elseif ($user->isCenterLevel()) {
            $filters['zone']   = $user->zone;
            $filters['center'] = $user->center; // associate directors locked to their center
        }

        // Cache key is scoped to this user + exact filter set + page + global version.
        // Incrementing members_cache_version (done by portal:warm-dashboard) instantly
        // invalidates all per-user caches without needing to enumerate them.
        $version  = (int) Cache::get('members_cache_version', 1);
        $cacheKey = 'members_' . $user->id . '_v' . $version . '_' . md5(serialize($filters) . $page . $perPage);

        try {
            $result = Cache::remember($cacheKey, 1800, fn() => $wa->getMembersPage($page, $perPage, $filters));
            $items  = $result['items'];
            $total  = $result['total'];
        } catch (\Throwable $e) {
            Log::error('Portal members page error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $items = [];
            $total = 0;
        }

        $members = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalCount    = $total;
        $filteredCount = $total;

        [$masjids, $zipCodes] = $this->getMembersFilterOptions($user);

        return view('portal.members.index', compact('members', 'totalCount', 'filteredCount', 'masjids', 'zipCodes'));
    }

    // ── Members filter options ────────────────────────────────────────────

    /**
     * Returns [masjids, zipCodes] scoped to what the user is authorised to see.
     * Sourced from the cached dashboard data — no extra API calls.
     * masjids: array of ['name' => string, 'value' => string]
     * zipCodes: array of zip code strings, sorted
     */
    private function getMembersFilterOptions(\App\Models\User $user): array
    {
        $dashData = Cache::get('wa_dashboard_data');

        if (! $dashData) {
            return [[], []];
        }

        // Apply the same scoping as the dashboard
        $scoped = $this->scopeDashboardData($dashData, $user);

        $masjids  = [];
        $zipCodes = [];

        foreach ($scoped['zones'] ?? [] as $zone) {
            foreach ($zone['centers'] ?? [] as $center) {
                $centerName = $center['name'] ?? '';
                if ($centerName) {
                    // value sent to getMembersPage filter is the DB center name;
                    // for city_wide/zone users we use the WA label as the filter value
                    // because getMembersPage does a substring match on it.
                    // Reverse-lookup from CENTER_WA_LABELS to get the DB key.
                    $dbKey = array_search($centerName, self::CENTER_WA_LABELS, true) ?: $centerName;
                    $masjids[] = ['name' => $centerName, 'value' => $dbKey];
                }

                foreach ($center['zips'] ?? [] as $zipRow) {
                    $zip = $zipRow['code'] ?? '';
                    if ($zip && ! in_array($zip, $zipCodes, true)) {
                        $zipCodes[] = $zip;
                    }
                }
            }
        }

        sort($zipCodes);

        return [$masjids, $zipCodes];
    }

    // ── CSV Export ────────────────────────────────────────────────────────

    public function exportCsv(Request $request, WildApricotService $wa)
    {
        $user     = Auth::user();
        $filename = 'isgh-members-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $filters = array_filter([
            'search' => $request->input('search', ''),
            'status' => $request->input('status', ''),
        ]);

        if ($user->isZoneLevel()) {
            $filters['zone'] = $user->zone;
        } elseif ($user->isCenterLevel()) {
            $filters['zone']   = $user->zone;
            $filters['center'] = $user->center;
        }

        $callback = function () use ($wa, $filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Name', 'Membership Type', 'Zone', 'Address', 'ZIP', 'Joined', 'Renewal', 'Status']);

            $page = 1;
            $i    = 0;
            do {
                try {
                    $result = $wa->getMembersPage($page, 500, $filters);
                } catch (\Throwable) {
                    break;
                }
                foreach ($result['items'] as $m) {
                    $i++;
                    fputcsv($handle, [
                        $i,
                        $m['name'],
                        $m['type'],
                        $m['zone'],
                        $m['address'],
                        $m['zip'],
                        $m['joined'],
                        $m['renewal'],
                        $m['status'],
                    ]);
                }
                $hasMore = count($result['items']) === 500;
                $page++;
            } while ($hasMore);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
