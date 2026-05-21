<?php

namespace App\Http\Controllers;

use App\Services\WildApricotService;
use Barryvdh\DomPDF\Facade\Pdf;
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
                'levelBreakdown' => [],
                'profileStatus'  => ['active' => 0, 'lapsed' => 0, 'active_pct' => 0, 'lapsed_pct' => 0],
                'zipStats'       => ['total' => 0],
                'zipData'        => collect(),
                'zones'          => [],
                '_warming'       => true,
            ];
        } else {
            // Defensive: the dashboard_stats / dashboard_centers `level_breakdown`
            // columns are JSON and the models cast to `array`, but in production
            // we've seen them arrive as raw JSON strings (corrupt write, missing
            // migration, or cast not applying). Decode here so every downstream
            // view sees an array — scopeDashboardData already does this per-centre,
            // but the top-level value for city-wide users skips that path.
            $data = $this->normaliseDashboardBreakdowns($data);
            $data = $this->scopeDashboardData($data, $user);
        }

        return view('portal.dashboard', $data);
    }

    /**
     * Ensure `levelBreakdown` (top level) and every centre's `level_breakdown`
     * are arrays, decoding from a JSON string if necessary. Safe to call even
     * when the values are already arrays.
     */
    private function normaliseDashboardBreakdowns(array $data): array
    {
        $toArray = static function ($v): array {
            if (is_array($v)) return $v;
            if (is_string($v) && $v !== '') {
                $decoded = json_decode($v, true);
                return is_array($decoded) ? $decoded : [];
            }
            return [];
        };

        $data['levelBreakdown'] = $toArray($data['levelBreakdown'] ?? []);

        if (!empty($data['zones']) && is_array($data['zones'])) {
            foreach ($data['zones'] as $zi => $zone) {
                if (!empty($zone['centers']) && is_array($zone['centers'])) {
                    foreach ($zone['centers'] as $ci => $center) {
                        $data['zones'][$zi]['centers'][$ci]['level_breakdown']
                            = $toArray($center['level_breakdown'] ?? []);
                    }
                }
            }
        }

        return $data;
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

    /** Filter-dropdown zone slug → dashboard zone name ("Southeast Zone"). */
    private const ZONE_SLUG_TO_NAME = [
        'north'     => 'North Zone',
        'northwest' => 'Northwest Zone',
        'south'     => 'South Zone',
        'southeast' => 'Southeast Zone',
        'southwest' => 'Southwest Zone',
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

        // Aggregate per-level counts across visible centers
        $levelTotals = [];
        foreach ($visibleCenters as $c) {
            $breakdown = $c['level_breakdown'] ?? [];
            if (is_string($breakdown)) {
                $breakdown = json_decode($breakdown, true) ?? [];
            }
            foreach ($breakdown as $row) {
                $levelTotals[$row['name']] = ($levelTotals[$row['name']] ?? 0) + ($row['count'] ?? 0);
            }
        }
        $data['levelBreakdown'] = array_values(array_map(
            fn($name, $count) => ['name' => $name, 'count' => $count],
            array_keys($levelTotals),
            array_values($levelTotals)
        ));

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
            'level'  => $request->input('level', ''),
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

        // Cascading dropdown options: Masjid narrowed to the in-effect zone,
        // ZIP narrowed to the in-effect zone and selected masjid.
        [$masjids, $zipCodes] = $this->getMembersFilterOptions(
            $user,
            $filters['zone']   ?? '',
            $filters['center'] ?? '',
        );

        // Membership Type dropdown options — every level configured in
        // WildApricot, sorted alphabetically. The filter submits a level's
        // exact Name, which getMembersPage() matches against MembershipLevelId.
        try {
            $waLevels = $wa->getMembershipLevels();
            $membershipLevels = collect($waLevels)
                ->pluck('Name')
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::error('Portal members: failed to load WA membership levels', ['error' => $e->getMessage()]);
            $membershipLevels = [];
        }

        return view('portal.members.index', compact('members', 'totalCount', 'filteredCount', 'masjids', 'zipCodes', 'membershipLevels'));
    }

    // ── Members filter options ────────────────────────────────────────────

    /**
     * Returns [masjids, zipCodes] scoped to what the user is authorised to see.
     * Sourced from the cached dashboard data — no extra API calls.
     *
     * Cascading narrowing:
     *  - $zoneSlug set   → masjids and zipCodes are limited to that zone.
     *  - $centerValue set → zipCodes are further limited to that one masjid.
     *
     * masjids:  array of ['name' => string, 'value' => string]
     * zipCodes: array of zip code strings, sorted
     */
    private function getMembersFilterOptions(
        \App\Models\User $user,
        string $zoneSlug = '',
        string $centerValue = '',
    ): array {
        $dashData = app(\App\Services\WildApricotService::class)->getDashboardFromDb();

        if (! $dashData) {
            return [[], []];
        }

        // Apply the same scoping as the dashboard
        $scoped = $this->scopeDashboardData($dashData, $user);

        // When a zone is selected, only that zone's centers/zips are offered.
        $zoneName = self::ZONE_SLUG_TO_NAME[$zoneSlug] ?? '';

        $masjids  = [];
        $zipCodes = [];

        foreach ($scoped['zones'] ?? [] as $zone) {
            if ($zoneName !== '' && ($zone['name'] ?? '') !== $zoneName) {
                continue;
            }

            foreach ($zone['centers'] ?? [] as $center) {
                $centerName = $center['name'] ?? '';
                if (! $centerName) {
                    continue;
                }

                // value sent to getMembersPage filter is the DB center name;
                // for city_wide/zone users we use the WA label as the filter value
                // because getMembersPage does a substring match on it.
                // Reverse-lookup from CENTER_WA_LABELS to get the DB key.
                $dbKey = array_search($centerName, self::CENTER_WA_LABELS, true) ?: $centerName;
                $masjids[] = ['name' => $centerName, 'value' => $dbKey];

                // When a masjid is selected, the ZIP list narrows to its zips only.
                if ($centerValue !== '' && $dbKey !== $centerValue && $centerName !== $centerValue) {
                    continue;
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

    // ── PDF Export ───────────────────────────────────────────────────────

    public function exportPdf(Request $request, WildApricotService $wa)
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
            'level'  => $request->input('level', ''),
        ]);

        if ($user->isZoneLevel()) {
            $filters['zone'] = $user->zone;
            if (!empty($filters['center'])) {
                $allowed = array_keys(self::CENTER_WA_LABELS);
                if (!in_array($filters['center'], $allowed, true)) {
                    unset($filters['center']);
                }
            }
        } elseif ($user->isCenterLevel()) {
            $filters['zone']   = $user->zone;
            $filters['center'] = $user->center;
        }

        try {
            $result = $wa->getMembersPage($page, $perPage, $filters);
            $items  = $result['items'];
            $total  = $result['total'];
        } catch (\Throwable $e) {
            Log::error('PDF export error', ['error' => $e->getMessage()]);
            $items = [];
            $total = 0;
        }

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset   = ($page - 1) * $perPage;

        // Build human-readable filter labels
        $statusMap = ['active' => 'Active', 'pending' => 'Pending', 'expired' => 'Expired', 'lapsed' => 'Lapsed'];
        $typeMap   = ['individual' => 'Individual', 'checkmatic' => 'Checkmatic', 'lifetime' => 'Lifetime'];
        $zoneMap   = [
            'north' => 'North Zone', 'northwest' => 'Northwest Zone', 'south' => 'South Zone',
            'southeast' => 'Southeast Zone', 'southwest' => 'Southwest Zone',
        ];

        $fZone   = $filters['zone']   ?? '';
        $fCenter = $filters['center'] ?? '';
        $fStatus = $filters['status'] ?? '';
        $fType   = $filters['type']   ?? '';
        $fSearch = $filters['search'] ?? '';

        $filterLabels = [
            'zone'   => $zoneMap[$fZone]   ?? ($fZone   ? ucfirst($fZone)   : null),
            'center' => $fCenter ?: null,
            'status' => $statusMap[$fStatus] ?? null,
            'type'   => $typeMap[$fType]   ?? null,
            'search' => $fSearch ?: null,
        ];

        $pdf = Pdf::loadView('portal.members.pdf', [
            'members'     => $items,
            'total'       => $total,
            'currentPage' => $page,
            'lastPage'    => $lastPage,
            'offset'      => $offset,
            'userRole'    => $user->roleLabel(),
            'filterLabels'=> $filterLabels,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true]);

        $filename = 'isgh-members-' . now()->format('Y-m-d') . '-page' . $page . '.pdf';

        return $pdf->download($filename);
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

    // ── Printable members list (new-tab HTML report) ──────────────────────

    /**
     * Renders a clean, browser-printable members report carrying the same
     * filters as the members page. Lists ALL filtered members (batch-fetched),
     * not just one page. Authorisation scope is re-enforced server-side so a
     * zone/center user cannot print outside their scope via URL edits.
     */
    public function printable(Request $request, WildApricotService $wa)
    {
        $user = Auth::user();

        $filters = array_filter([
            'search' => $request->input('search', ''),
            'status' => $request->input('status', ''),
            'zone'   => $request->input('zone', ''),
            'center' => $request->input('center', ''),
            'zip'    => $request->input('zip', ''),
            'type'   => $request->input('type', ''),
            'level'  => $request->input('level', ''),
        ]);

        // Enforce the user's authorised scope (mirrors members() / exportPdf()).
        if ($user->isZoneLevel()) {
            $filters['zone'] = $user->zone;
            if (!empty($filters['center'])) {
                $allowed = array_keys(self::CENTER_WA_LABELS);
                if (!in_array($filters['center'], $allowed, true)) {
                    unset($filters['center']);
                }
            }
        } elseif ($user->isCenterLevel()) {
            $filters['zone']   = $user->zone;
            $filters['center'] = $user->center;
        }

        // Fetch every filtered member in batches (same loop as exportCsv).
        $members = [];
        $page    = 1;
        do {
            try {
                $result = $wa->getMembersPage($page, 500, $filters);
            } catch (\Throwable $e) {
                Log::error('Printable members list error', ['error' => $e->getMessage()]);
                break;
            }
            foreach ($result['items'] as $m) {
                $members[] = $m;
            }
            $hasMore = count($result['items']) === 500;
            $page++;
        } while ($hasMore);

        // Human-readable applied-filter labels (mirrors exportPdf()).
        $statusMap = ['active' => 'Active', 'pending' => 'Pending', 'expired' => 'Expired', 'lapsed' => 'Lapsed'];
        $typeMap   = ['individual' => 'Individual', 'checkmatic' => 'Checkmatic', 'lifetime' => 'Lifetime'];
        $zoneMap   = [
            'north' => 'North Zone', 'northwest' => 'Northwest Zone', 'south' => 'South Zone',
            'southeast' => 'Southeast Zone', 'southwest' => 'Southwest Zone',
        ];

        $fZone   = $filters['zone']   ?? '';
        $fStatus = $filters['status'] ?? '';
        $fType   = $filters['type']   ?? '';

        $filterLabels = [
            'zone'   => $zoneMap[$fZone] ?? ($fZone ? ucfirst($fZone) : null),
            'center' => ($filters['center'] ?? '') ?: null,
            'status' => $statusMap[$fStatus] ?? null,
            'type'   => $typeMap[$fType] ?? null,
            'search' => ($filters['search'] ?? '') ?: null,
        ];

        return view('portal.members.print', [
            'members'      => $members,
            'total'        => count($members),
            'generatedAt'  => now(),
            'userRole'     => $user->roleLabel(),
            'filterLabels' => $filterLabels,
        ]);
    }
}
