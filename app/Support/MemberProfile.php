<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * View-model wrapping a raw WildApricot member bundle.
 *
 * The bundle shape: ['contact'=>array, 'family'=>array[], 'invoices'=>array[], 'payments'=>array[]].
 * This class is the SINGLE place that knows WildApricot's FieldValues structure.
 * All accessors are safe — missing/malformed data yields '' or [] and never throws.
 */
class MemberProfile
{
    public string $firstName;
    public string $lastName;
    public string $fullName;
    public string $email;
    public string $phone;
    public string $status;
    public string $level;
    public string $street;
    public string $city;
    public string $state;
    public string $zip;
    public string $dob;
    public string $txId;
    public string $zone;
    public string $memberSince;
    public string $renewalDue;
    public string $yearlyFee;

    /** @var array<int,MemberProfile> */
    public array $family = [];
    /** @var array<int,array> */
    public array $invoices = [];
    /** @var array<int,array> */
    public array $payments = [];

    private array $contact;

    public function __construct(array $bundle)
    {
        // A bundle may be a full bundle or just a bare contact array (used for family members).
        $this->contact = $bundle['contact'] ?? $bundle;

        $this->firstName   = (string) ($this->contact['FirstName'] ?? '');
        $this->lastName    = (string) ($this->contact['LastName'] ?? '');
        $this->fullName    = trim($this->firstName . ' ' . $this->lastName);
        $this->email       = (string) ($this->contact['Email'] ?? '');
        $this->status      = (string) ($this->contact['Status'] ?? 'Active');
        $this->level       = (string) ($this->contact['MembershipLevel']['Name'] ?? '');

        $this->phone       = ($this->contact['Phone'] ?? '') !== '' ? (string) $this->contact['Phone'] : $this->field('Cell Phone', 'custom-9967571');
        $this->street      = $this->field('Street Address', 'custom-9967566');
        $this->city        = $this->field('City', 'custom-9967567');
        $this->state       = $this->field('State', 'custom-9967569');
        $this->zip         = $this->field('ZIP', 'custom-9967570');
        $this->dob         = $this->field('Date of Birth', 'custom-10694881');
        $this->txId        = $this->field('TX DL/ID Number', 'custom-17846913');
        $this->zone        = $this->field('Zone / Center', 'custom-9967573');
        $this->memberSince = $this->field('Member since', 'MemberSince');
        $this->renewalDue  = $this->field('Renewal due', 'RenewalDue');
        $this->yearlyFee   = $this->field('Membership fee', 'MembershipFee');

        // Filled by later tasks (family, invoices, payments). Defaults keep this task green.
    }

    /**
     * Read a value from the contact's FieldValues by FieldName or SystemCode.
     * Choice values ({Id,Label}) are unwrapped to their Label string.
     */
    private function field(string $fieldName, string $systemCode = ''): string
    {
        $fvs = $this->contact['FieldValues'] ?? [];
        if (! is_array($fvs)) {
            return '';
        }
        foreach ($fvs as $fv) {
            if (! is_array($fv)) {
                continue;
            }
            $name = $fv['FieldName'] ?? '';
            $code = $fv['SystemCode'] ?? '';
            if ($name === $fieldName || ($systemCode !== '' && $code === $systemCode)) {
                $val = $fv['Value'] ?? '';
                if (is_array($val)) {
                    return (string) ($val['Label'] ?? '');
                }
                return (string) $val;
            }
        }
        return '';
    }
}
