<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Classifies a Wild Apricot membership lookup into one of the verification
 * scenarios and supplies the shared values used by popups and emails.
 */
class MembershipVerification
{
    public const ACTIVE_LATE     = 'active_late';      // Scenario 1
    public const NOT_FOUND       = 'not_found';        // Scenario 2
    public const LAPSED          = 'lapsed';           // Scenario 3
    public const ACTIVE_ELIGIBLE = 'active_eligible';  // Scenario 4
    public const OTHER           = 'other';            // any other status (e.g. pending)

    public const NOT_ON_FILE = 'Not on file';

    public static function scenario(string $status, ?string $registrationRaw): string
    {
        $status = strtolower(trim($status));

        if ($status === 'lapsed') {
            return self::LAPSED;
        }

        if ($status !== 'active') {
            return self::OTHER;
        }

        return self::registeredAfterCutoff($registrationRaw)
            ? self::ACTIVE_LATE
            : self::ACTIVE_ELIGIBLE;
    }

    /** True only when a parseable registration date falls after the cutoff. */
    public static function registeredAfterCutoff(?string $registrationRaw): bool
    {
        if (! $registrationRaw) {
            return false;
        }

        try {
            $registered = Carbon::parse($registrationRaw)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        return $registered->gt(Carbon::parse(config('membership.verification.voting_cutoff'))->startOfDay());
    }

    public static function display(?string $value): string
    {
        $value = trim((string) $value);

        return ($value === '' || $value === '—') ? self::NOT_ON_FILE : $value;
    }

    public static function formatDate(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return self::NOT_ON_FILE;
        }
        if (strtolower($raw) === 'never') {
            return $raw;
        }
        try {
            return Carbon::parse($raw)->format('m-d-Y');
        } catch (\Throwable) {
            return $raw;
        }
    }

    public static function internalEmail(): string
    {
        return config('membership.verification.internal_email');
    }

    /** Shared footer values for every member-facing email. */
    public static function contact(): array
    {
        return [
            'phone'   => config('membership.verification.phone'),
            'website' => config('membership.verification.website'),
            'email'   => self::internalEmail(),
        ];
    }

    public static function renewalLink(): string
    {
        return config('membership.verification.renewal_link') ?: route('member-portal.login');
    }
}
