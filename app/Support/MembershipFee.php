<?php

namespace App\Support;

/**
 * Resolves a membership fee for a type. Shared by RenewalService and
 * LevelChangeService so the fee math lives in one place.
 *
 * - flat:        $20 * (1 primary + $familyCount).
 * - checkomatic: the member-entered monthly amount.
 * - else:        the flat fee from config/membership.php.
 */
class MembershipFee
{
    /** @return array{cents:int,label:string} */
    public static function resolve(string $type, int $familyCount, ?float $checkomaticAmount): array
    {
        if ($type === 'flat') {
            $perMember = (int) (config('membership.fees')['flat']['cents'] ?? 2000);
            $cents = (1 + max(0, $familyCount)) * $perMember;
            return ['cents' => $cents, 'label' => '$' . number_format($cents / 100, 2)];
        }

        if ($type === 'checkomatic_family' || $type === 'checkomatic_individual') {
            $amount = (float) ($checkomaticAmount ?? 0);
            $cents  = (int) round($amount * 100);
            return ['cents' => $cents, 'label' => '$' . number_format($amount, 2) . '/mo'];
        }

        $entry = config('membership.fees')[$type] ?? ['cents' => 0, 'label' => '$0.00'];
        return ['cents' => (int) $entry['cents'], 'label' => (string) $entry['label']];
    }
}
