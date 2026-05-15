<?php

namespace App\Services;

use App\Support\MemberProfile;
use RuntimeException;

/**
 * Orchestrates a membership renewal: resolves the fee, runs the Stripe charge
 * sequence, and dispatches the WildApricot renewal job. Lifetime memberships
 * are not renewable and are rejected by resolveTypeSlug().
 */
class RenewalService
{
    /**
     * Maps a WildApricot membership-level NAME to a membership-type slug.
     * This is the inverse of WildApricotService::resolveLevelId()'s name map.
     */
    private const LEVEL_NAME_TO_SLUG = [
        'Family Membership (Primary and Spouse only)'              => 'family',
        'Individual'                                               => 'individual',
        'Flat Membership'                                          => 'flat',
        'Checkomatic Membership (Primary and Spouse only)'         => 'checkomatic_family',
        'Checkomatic'                                              => 'checkomatic_individual',
        'Lifetime'                                                 => 'lifetime_individual',
    ];

    private const LIFETIME_SLUGS = ['lifetime_family', 'lifetime_individual'];

    /**
     * Resolve the member's membership-type slug from their WA level name.
     * @throws RuntimeException when the level is a lifetime (non-renewable) type.
     */
    public function resolveTypeSlug(MemberProfile $profile): string
    {
        $slug = $this->resolveSlug($profile);

        if (in_array($slug, self::LIFETIME_SLUGS, true)) {
            throw new RuntimeException(
                'Lifetime membership (' . trim($profile->level) . ') is not renewable.'
            );
        }

        return $slug;
    }

    /** True when the member's WA level is a non-renewable lifetime plan. */
    public function isLifetimeLevel(MemberProfile $profile): bool
    {
        return in_array($this->resolveSlug($profile), self::LIFETIME_SLUGS, true);
    }

    /**
     * Map the member's WA membership-level name to a membership-type slug.
     * Uses the LEVEL_NAME_TO_SLUG table, with a substring-based fallback for
     * level names not in the table.
     */
    private function resolveSlug(MemberProfile $profile): string
    {
        $levelName = trim($profile->level);
        $slug = self::LEVEL_NAME_TO_SLUG[$levelName] ?? null;
        if ($slug !== null) {
            return $slug;
        }

        $lower = strtolower($levelName);
        return match (true) {
            str_contains($lower, 'lifetime')    => 'lifetime_individual',
            str_contains($lower, 'checkomatic') => str_contains($lower, 'family') ? 'checkomatic_family' : 'checkomatic_individual',
            str_contains($lower, 'flat')        => 'flat',
            str_contains($lower, 'family')      => 'family',
            default                             => 'individual',
        };
    }

    /**
     * Resolve the renewal fee for a membership type.
     *
     * - flat:        $20 * (1 primary + $familyCount).
     * - checkomatic: the member-entered monthly amount.
     * - else:        the flat fee from config/membership.php.
     *
     * @return array{cents:int,label:string}
     */
    public function resolveFee(string $type, int $familyCount, ?float $checkomaticAmount): array
    {
        if ($type === 'flat') {
            $cents = (1 + max(0, $familyCount)) * 2000;
            return ['cents' => $cents, 'label' => '$' . number_format($cents / 100, 2)];
        }

        if ($type === 'checkomatic_family' || $type === 'checkomatic_individual') {
            $amount = (float) ($checkomaticAmount ?? 0);
            $cents  = (int) round($amount * 100);
            return ['cents' => $cents, 'label' => '$' . number_format($amount, 2) . '/mo'];
        }

        $fees = config('membership.fees');
        $entry = $fees[$type] ?? ['cents' => 0, 'label' => '$0.00'];
        return ['cents' => (int) $entry['cents'], 'label' => (string) $entry['label']];
    }

    /**
     * Build the data the renewal modal needs.
     * For checkomatic the fee cents are 0 until the member enters an amount.
     *
     * @return array{type:string,isCheckomatic:bool,fee:array,newRenewalDate:string,familyCount:int}
     */
    public function buildSummary(MemberProfile $profile): array
    {
        $type          = $this->resolveTypeSlug($profile);
        $isCheckomatic = str_starts_with($type, 'checkomatic');
        $familyCount   = count($profile->family);
        $fee           = $this->resolveFee($type, $familyCount, $isCheckomatic ? null : 0.0);

        return [
            'type'           => $type,
            'isCheckomatic'  => $isCheckomatic,
            'fee'            => $fee,
            'newRenewalDate' => $this->newRenewalDate($type),
            'familyCount'    => $familyCount,
        ];
    }

    /** The renewal date a successful renewal will set: end of next calendar year. */
    public function newRenewalDate(string $type): string
    {
        if (str_starts_with($type, 'checkomatic')) {
            return now()->addMonth()->format('F d, Y');
        }
        return now()->addYear()->endOfYear()->format('F d, Y');
    }
}
