<?php

namespace App\Console\Commands;

use App\Services\WildApricotService;
use App\Services\ZipCenterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FixMemberZones extends Command
{
    protected $signature = 'members:fix-zones
                            {--dry-run : Print what would be changed without writing to WildApricot}';

    protected $description = 'For each given member ID: if no Zone/Center is set, assign one from ZIP lookup and add to "Voting Members 2026".';

    public function __construct(
        private WildApricotService $wa,
        private ZipCenterService   $zip,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $idsFile = storage_path('app/private/member_ids.txt');
        if (! file_exists($idsFile)) {
            $this->error("Member IDs file not found: {$idsFile}");
            return self::FAILURE;
        }

        $ids = array_filter(
            array_map('intval', preg_split('/[\s,]+/', file_get_contents($idsFile))),
            fn ($id) => $id > 0
        );

        if (empty($ids)) {
            $this->error('No valid member IDs found in the file.');
            return self::FAILURE;
        }

        $this->info('Loaded ' . count($ids) . ' member IDs from ' . $idsFile);

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[dry-run] No changes will be written to WildApricot.');
        }

        $updated = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($ids as $contactId) {
            $this->line("-- Contact {$contactId}");

            try {
                $contact = $this->fetchContact($contactId);
                if (! $contact) {
                    $this->error("  FAIL: Could not fetch contact {$contactId} from WildApricot.");
                    $failed++;
                    continue;
                }

                $currentZone = $this->extractField($contact, 'Zone / Center', 'custom-9967573');
                if ($currentZone !== '') {
                    $this->line("  INFO: Zone already set to [{$currentZone}] — skipping zone update, will still assign group.");
                }

                $name = trim(($contact['FirstName'] ?? '') . ' ' . ($contact['LastName'] ?? ''));

                if ($currentZone === '') {
                    $memberZip = $this->extractField($contact, 'ZIP', 'custom-9967570');
                    if ($memberZip === '') {
                        $this->warn("  WARN: No ZIP on file — cannot determine zone, will still assign group.");
                    } else {
                        $lookup  = $this->zip->lookup($memberZip);
                        $centers = $lookup['centers'] ?? [];
                        if (empty($centers)) {
                            $this->warn("  WARN: ZIP {$memberZip} not found in ZIP->center mapping — skipping zone, will still assign group.");
                        } else {
                            $zoneLabel = $centers[0];
                            $this->info("  -> Assign zone [{$zoneLabel}] (ZIP {$memberZip}) to {$name}");
                            if (! $dryRun) {
                                $this->wa->setContactZone($contactId, $zoneLabel);
                                Log::info('members:fix-zones: zone set', [
                                    'contact_id' => $contactId,
                                    'name'       => $name,
                                    'zone'       => $zoneLabel,
                                    'zip'        => $memberZip,
                                ]);
                            } else {
                                $this->line("  [dry-run] Would set zone to [{$zoneLabel}].");
                            }
                        }
                    }
                }

                $this->info("  -> Add 'Voting Members 2026' to {$name}");
                if (! $dryRun) {
                    $this->wa->addGroupParticipation($contactId, 'Voting Members 2026');
                    $this->info("  OK: Group participation updated.");
                    Log::info('members:fix-zones: group set', ['contact_id' => $contactId, 'name' => $name]);
                } else {
                    $this->line("  [dry-run] Would add 'Voting Members 2026'.");
                }

                $updated++;

            } catch (Throwable $e) {
                $this->error("  FAIL: Error on contact {$contactId}: " . $e->getMessage());
                Log::error('members:fix-zones error', ['contact_id' => $contactId, 'error' => $e->getMessage()]);
                $failed++;
            }
        }

        $this->newLine();
        $this->table(
            ['Updated', 'Skipped', 'Failed'],
            [[$updated, $skipped, $failed]],
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function fetchContact(int $contactId): ?array
    {
        $accountId = $this->wa->getAccountId();

        $r = Http::withToken($this->wa->getAccessToken())
            ->acceptJson()
            ->connectTimeout(4)
            ->timeout(30)
            ->get("https://api.wildapricot.org/v2.3/accounts/{$accountId}/contacts/{$contactId}");

        if (! $r->successful()) {
            Log::warning('members:fix-zones: contact fetch failed', [
                'contact_id' => $contactId,
                'status'     => $r->status(),
                'body'       => substr($r->body(), 0, 500),
            ]);
            return null;
        }

        return $r->json();
    }

    private function extractField(array $contact, string $fieldName, string $systemCode): string
    {
        foreach ($contact['FieldValues'] ?? [] as $fv) {
            if (($fv['FieldName'] ?? '') === $fieldName || ($fv['SystemCode'] ?? '') === $systemCode) {
                $val = $fv['Value'] ?? '';
                if (is_array($val)) {
                    return (string) ($val['Label'] ?? '');
                }
                return trim((string) $val);
            }
        }
        return '';
    }
}
