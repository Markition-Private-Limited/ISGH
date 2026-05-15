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
        $levelName = trim($profile->level);
        $slug = self::LEVEL_NAME_TO_SLUG[$levelName] ?? null;

        // Fallback: a level name containing "lifetime"/"checkomatic"/"family"/"flat".
        if ($slug === null) {
            $lower = strtolower($levelName);
            $slug = match (true) {
                str_contains($lower, 'lifetime')   => 'lifetime_individual',
                str_contains($lower, 'checkomatic')=> str_contains($lower, 'family') ? 'checkomatic_family' : 'checkomatic_individual',
                str_contains($lower, 'flat')       => 'flat',
                str_contains($lower, 'family')     => 'family',
                default                            => 'individual',
            };
        }

        if (in_array($slug, self::LIFETIME_SLUGS, true)) {
            throw new RuntimeException("Lifetime membership ({$levelName}) is not renewable.");
        }

        return $slug;
    }

    /** True when a membership-type slug is a non-renewable lifetime plan. */
    public function isLifetimeLevel(MemberProfile $profile): bool
    {
        $levelName = trim($profile->level);
        $slug = self::LEVEL_NAME_TO_SLUG[$levelName] ?? null;
        if ($slug === null) {
            return str_contains(strtolower($levelName), 'lifetime');
        }
        return in_array($slug, self::LIFETIME_SLUGS, true);
    }
}
