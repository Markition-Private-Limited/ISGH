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
];
