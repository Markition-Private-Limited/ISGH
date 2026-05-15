<?php

// Reusable WildApricot bundle fixture for tests.
// Returns ['contact'=>…, 'family'=>[…], 'invoices'=>[…], 'payments'=>[…]].

return [
    'contact' => [
        'Id'        => 999,
        'FirstName' => 'Tauqeer',
        'LastName'  => 'Alam',
        'Email'     => 'tauqeer@example.com',
        'Phone'     => '2154389281',
        'Status'    => 'Active',
        'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual Membership'],
        'FieldValues' => [
            ['FieldName' => 'Street Address', 'SystemCode' => 'custom-9967566', 'Value' => '7829 Southwest Freeway'],
            ['FieldName' => 'City',           'SystemCode' => 'custom-9967567', 'Value' => 'Texas City'],
            ['FieldName' => 'State',          'SystemCode' => 'custom-9967569', 'Value' => 'Texas'],
            ['FieldName' => 'ZIP',            'SystemCode' => 'custom-9967570', 'Value' => '78933'],
            ['FieldName' => 'Date of Birth',  'SystemCode' => 'custom-10694881','Value' => '2005-11-09'],
            ['FieldName' => 'TX DL/ID Number','SystemCode' => 'custom-17846913','Value' => '2427896483965'],
            ['FieldName' => 'Zone / Center',  'SystemCode' => 'custom-9967573', 'Value' => ['Id' => 7, 'Label' => 'Spring Branch Islamic Center']],
            ['FieldName' => 'Member since',   'SystemCode' => 'MemberSince',    'Value' => '2021-08-22T00:00:00'],
            ['FieldName' => 'Renewal due',    'SystemCode' => 'RenewalDue',     'Value' => '2027-01-15T00:00:00'],
        ],
    ],
    'family' => [
        [
            'Id'        => 1001,
            'FirstName' => 'Sarah',
            'LastName'  => 'Alam',
            'Email'     => 'sarah@example.com',
            'Phone'     => '2155256151',
            'Status'    => 'Active',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual Membership'],
            'FieldValues' => [
                ['FieldName' => 'City',  'SystemCode' => 'custom-9967567', 'Value' => 'Texas City'],
                ['FieldName' => 'State', 'SystemCode' => 'custom-9967569', 'Value' => 'Texas'],
            ],
        ],
    ],
    'invoices' => [
        ['Id' => 1, 'DocumentNumber' => 'INV-2026-0001', 'Value' => 20.0, 'IsPaid' => true,  'CreatedDate' => '2026-01-15T00:00:00'],
        ['Id' => 2, 'DocumentNumber' => 'INV-2026-0002', 'Value' => 20.0, 'IsPaid' => false, 'CreatedDate' => '2026-06-15T00:00:00'],
    ],
    'payments' => [
        ['Id' => 5, 'Value' => 20.0, 'CreatedDate' => '2026-01-15T00:00:00'],
        ['Id' => 6, 'Value' => 60.0, 'CreatedDate' => '2025-03-10T00:00:00'],
    ],
    // Annual membership fee resolved from the contact's membership level.
    'membershipFee' => 200.0,
];
