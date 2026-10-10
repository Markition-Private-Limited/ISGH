<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Membership Fees
    |--------------------------------------------------------------------------
    | Shared by the public signup flow (MembershipController) and the member
    | portal renewal flow (RenewalService). Keyed by membership-type slug.
    */
    'fees' => [
        'family'                 => ['cents' => 4000,   'label' => '$40.00'],
        'individual'             => ['cents' => 2000,   'label' => '$20.00'],
        'flat'                   => ['cents' => 2000,   'label' => '$20.00 / member'],
        'checkomatic_family'     => ['cents' => 1000,   'label' => '$10.00/mo'],
        'checkomatic_individual' => ['cents' => 1000,   'label' => '$10.00/mo'],
        'lifetime_family'        => ['cents' => 150000, 'label' => '$1,500.00'],
        'lifetime_individual'    => ['cents' => 100000, 'label' => '$1,000.00'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Membership Verification (public lookup page)
    |--------------------------------------------------------------------------
    | Drives the popups and emails for the verification flow. Placeholders that
    | ISGH supplies (renewal link, phone, website) are env-overridable.
    */
    'verification' => [
        'internal_email'    => env('MEMBERSHIP_VERIFY_EMAIL', 'membership.verify@isgh.org'),
        // Last day a registration / renewal counted toward 2026 voting eligibility.
        'voting_cutoff'     => env('MEMBERSHIP_VOTING_CUTOFF', '2026-06-30'),
        'renewal_link'      => env('MEMBERSHIP_RENEWAL_LINK'),
        'phone'             => env('MEMBERSHIP_PHONE', '(713) 524-6615 ext 105/108'),
        'website'           => env('MEMBERSHIP_WEBSITE', 'https://www.isgh.org'),
        'review_timeline'   => '3 to 5 business days',
    ],
];
