<?php

namespace App\Services;

use App\Support\MemberProfile;
use App\Support\MembershipFee;
use App\Support\MembershipTypes;

/**
 * Orchestrates a membership level change: lists the available target levels,
 * resolves the target-level fee, runs the Stripe charge, and dispatches the
 * WildApricot level-change job.
 */
class LevelChangeService
{
    public function __construct(private StripeService $stripe) {}

    /**
     * The membership types the member may switch to — all 7 minus their
     * current one. Each: {type, label, fee, includesFamily, isCheckomatic}.
     * For checkomatic types the fee cents are 0 until the member enters an amount.
     */
    public function availableLevels(MemberProfile $profile): array
    {
        $currentSlug = MembershipTypes::slugFromLevelName($profile->level);

        $levels = [];
        foreach (MembershipTypes::allSlugs() as $slug) {
            if ($slug === $currentSlug) {
                continue;
            }
            $isCheckomatic = MembershipTypes::isCheckomatic($slug);
            $levels[] = [
                'type'           => $slug,
                'label'          => MembershipTypes::labelForSlug($slug),
                'fee'            => MembershipFee::resolve($slug, 0, $isCheckomatic ? null : 0.0),
                'includesFamily' => MembershipTypes::includesFamily($slug),
                'isCheckomatic'  => $isCheckomatic,
            ];
        }

        return $levels;
    }
}
