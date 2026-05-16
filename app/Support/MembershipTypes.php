<?php

namespace App\Support;

/**
 * Shared membership-type knowledge: the WildApricot level-name -> slug map,
 * type classification helpers, and human labels. Used by RenewalService and
 * LevelChangeService so the mapping lives in exactly one place.
 */
class MembershipTypes
{
    /** WildApricot membership-level NAME => membership-type slug. */
    public const LEVEL_NAME_TO_SLUG = [
        'Family Membership (Primary and Spouse only)'      => 'family',
        'Individual'                                       => 'individual',
        'Flat Membership'                                  => 'flat',
        'Checkomatic Membership (Primary and Spouse only)' => 'checkomatic_family',
        'Checkomatic'                                      => 'checkomatic_individual',
        'Lifetime'                                         => 'lifetime_individual',
    ];

    /** Human-readable label for each slug. */
    public const SLUG_LABELS = [
        'family'                 => 'Family Membership',
        'individual'             => 'Individual Membership',
        'flat'                   => 'Flat Membership',
        'checkomatic_family'     => 'Checkomatic Family',
        'checkomatic_individual' => 'Checkomatic Individual',
        'lifetime_family'        => 'Lifetime Family',
        'lifetime_individual'    => 'Lifetime Individual',
    ];

    private const LIFETIME_SLUGS = ['lifetime_family', 'lifetime_individual'];
    private const FAMILY_SLUGS   = ['family', 'checkomatic_family', 'lifetime_family', 'flat'];

    /** All 7 membership-type slugs. */
    public static function allSlugs(): array
    {
        return array_keys(self::SLUG_LABELS);
    }

    /**
     * Map a WildApricot membership-level name to a membership-type slug.
     * Uses the LEVEL_NAME_TO_SLUG table, with a substring fallback.
     */
    public static function slugFromLevelName(string $levelName): string
    {
        $levelName = trim($levelName);
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

    /** Human label for a slug, or the slug itself if unknown. */
    public static function labelForSlug(string $slug): string
    {
        return self::SLUG_LABELS[$slug] ?? $slug;
    }

    /** True when the slug is a lifetime plan. */
    public static function isLifetime(string $slug): bool
    {
        return in_array($slug, self::LIFETIME_SLUGS, true);
    }

    /** True when the slug is a checkomatic (recurring monthly) plan. */
    public static function isCheckomatic(string $slug): bool
    {
        return str_starts_with($slug, 'checkomatic');
    }

    /** True when the slug's plan includes a spouse/family. */
    public static function includesFamily(string $slug): bool
    {
        return in_array($slug, self::FAMILY_SLUGS, true);
    }
}
