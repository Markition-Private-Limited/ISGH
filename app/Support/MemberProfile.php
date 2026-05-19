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
        // MemberSince / RenewalDue are returned by WildApricot as TOP-LEVEL contact
        // properties (see WildApricotService::createActiveMember). Prefer those;
        // fall back to FieldValues only if the top-level key is absent.
        $this->memberSince = $this->topLevelOrField('MemberSince', 'Member since', 'MemberSince');
        $this->renewalDue  = $this->topLevelOrField('RenewalDue', 'Renewal due', 'RenewalDue');

        // Yearly fee — prefer the membership-level fee resolved by
        // MemberPortalService; fall back to a 'Membership fee' FieldValue.
        $this->yearlyFee = $this->resolveYearlyFee($bundle['membershipFee'] ?? null);

        // Family members — each wrapped in its own MemberProfile.
        foreach (($bundle['family'] ?? []) as $fam) {
            if (is_array($fam)) {
                $this->family[] = new MemberProfile(['contact' => $fam]);
            }
        }

        // Invoices — normalized to a predictable shape.
        foreach (($bundle['invoices'] ?? []) as $inv) {
            if (! is_array($inv)) {
                continue;
            }
            $iso = $this->isoToDate($inv['CreatedDate'] ?? '');
            $this->invoices[] = [
                'id'        => $inv['Id'] ?? null,
                'number'    => (string) ($inv['DocumentNumber'] ?? ('INV-' . ($inv['Id'] ?? ''))),
                'date'      => $iso,
                'dateLabel' => $this->formatDate($iso),
                'amount'    => (float) ($inv['Value'] ?? 0),
                'isPaid'    => (bool) ($inv['IsPaid'] ?? false),
                'url'       => (string) ($inv['Url'] ?? '#'),
            ];
        }

        // Payments — normalized.
        foreach (($bundle['payments'] ?? []) as $pay) {
            if (! is_array($pay)) {
                continue;
            }
            $payIso = $this->isoToDate($pay['CreatedDate'] ?? '');
            $this->payments[] = [
                'date'      => $payIso,
                'dateLabel' => $this->formatDate($payIso),
                'amount'    => (float) ($pay['Value'] ?? 0),
            ];
        }
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

    /**
     * Read a value that WildApricot may return as a top-level contact key
     * (e.g. MemberSince, RenewalDue) OR inside FieldValues. The top-level key
     * wins; FieldValues is the fallback. Returns '' when neither is present.
     */
    private function topLevelOrField(string $topKey, string $fieldName, string $systemCode = ''): string
    {
        $top = $this->contact[$topKey] ?? null;
        if (is_array($top)) {
            $top = $top['Value'] ?? ($top['Label'] ?? null);
        }
        if ($top !== null && $top !== '' && strtolower((string) $top) !== 'null') {
            return (string) $top;
        }
        return $this->field($fieldName, $systemCode);
    }

    /**
     * Resolve the yearly fee as a formatted currency string.
     * Uses the membership-level fee when available, else a 'Membership fee'
     * FieldValue, else '' (the view renders a placeholder).
     */
    private function resolveYearlyFee(?float $levelFee): string
    {
        if ($levelFee !== null) {
            return '$' . number_format($levelFee, 2);
        }
        $fv = $this->field('Membership fee', 'MembershipFee');
        if ($fv === '') {
            return '';
        }
        // FieldValue may already include a currency symbol; normalise numerics.
        return is_numeric($fv) ? '$' . number_format((float) $fv, 2) : $fv;
    }

    /** True when the membership status indicates a lapsed membership. */
    public function isExpired(): bool
    {
        $s = strtolower($this->status);
        return in_array($s, ['expired', 'lapsed', 'overdue'], true);
    }

    /** Renewal date as "January 15, 2027", or '' if unparseable. */
    public function renewalFormatted(): string
    {
        return $this->formatDate($this->renewalDue);
    }

    /** Member-since date as "August 22, 2021", or '' if unparseable. */
    public function memberSinceFormatted(): string
    {
        return $this->formatDate($this->memberSince);
    }

    /** Date of birth as "November 09, 2005", or '' if unparseable. */
    public function dobFormatted(): string
    {
        return $this->formatDate($this->dob);
    }

    /**
     * Date of birth as "YYYY-MM-DD" — the only format an <input type="date">
     * accepts. WildApricot returns a full ISO datetime ("2000-03-25T00:00:00"),
     * which the date input silently rejects and renders blank. Returns '' if
     * the stored value is empty or unparseable.
     */
    public function dobInput(): string
    {
        if ($this->dob === '' || strtolower($this->dob) === 'null') {
            return '';
        }
        try {
            return Carbon::parse($this->dob)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    /** Date of birth as "MM/DD/YYYY", or '' if empty or unparseable. */
    public function dobMdy(): string
    {
        if ($this->dob === '' || strtolower($this->dob) === 'null') {
            return '';
        }
        try {
            return Carbon::parse($this->dob)->format('m/d/Y');
        } catch (\Throwable) {
            return '';
        }
    }

    /** Whole days until renewal; null when renewal is in the past or unknown. */
    public function daysLeft(): ?int
    {
        $diff = $this->renewalDiffDays();
        return ($diff !== null && $diff >= 0) ? $diff : null;
    }

    /** Whole days a renewal is overdue; null when not overdue or unknown. */
    public function daysOverdue(): ?int
    {
        $diff = $this->renewalDiffDays();
        return ($diff !== null && $diff < 0) ? abs($diff) : null;
    }

    private function renewalDiffDays(): ?int
    {
        if ($this->renewalDue === '') {
            return null;
        }
        try {
            return (int) now()->startOfDay()->diffInDays(Carbon::parse($this->renewalDue)->startOfDay(), false);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDate(string $value): string
    {
        if ($value === '' || strtolower($value) === 'null' || strtolower($value) === 'never') {
            return '';
        }
        try {
            return Carbon::parse($value)->format('F d, Y');
        } catch (\Throwable) {
            return '';
        }
    }

    /** True when the member has at least one family member. */
    public function hasFamily(): bool
    {
        return $this->family !== [];
    }

    /** True when the member has a spouse (first family member). */
    public function hasSpouse(): bool
    {
        return isset($this->family[0]);
    }

    /** The spouse (first family member), or null. */
    public function spouse(): ?MemberProfile
    {
        return $this->family[0] ?? null;
    }

    /** True when the member has at least one invoice. */
    public function hasInvoices(): bool
    {
        return $this->invoices !== [];
    }

    /** Earliest unpaid invoice with a known date as ['amount'=>float,'date'=>string], or null. */
    public function nextPayment(): ?array
    {
        $unpaid = array_values(array_filter(
            $this->invoices,
            fn ($i) => ! $i['isPaid'] && $i['date'] !== ''
        ));
        if ($unpaid === []) {
            return null;
        }
        usort($unpaid, fn ($a, $b) => strcmp($a['date'], $b['date']));
        return ['amount' => $unpaid[0]['amount'], 'date' => $unpaid[0]['date'], 'dateLabel' => $unpaid[0]['dateLabel']];
    }

    /**
     * Billing-period label for an invoice row, derived from the membership level:
     *  - lifetime levels never renew → '' (blank)
     *  - checkomatic levels bill monthly → invoice month + 1 month
     *  - all other (annual) levels → invoice month + 1 year
     * Expects an invoice array as produced in the constructor (uses 'date').
     * Returns '' when the invoice date is missing or unparseable.
     */
    public function billingPeriod(array $invoice): string
    {
        $date = (string) ($invoice['date'] ?? '');
        if ($date === '') {
            return '';
        }

        $level = strtolower($this->level);
        if (str_contains($level, 'lifetime')) {
            return '';
        }

        try {
            $start = Carbon::parse($date);
        } catch (\Throwable) {
            return '';
        }

        $end = str_contains($level, 'checkomatic')
            ? $start->copy()->addMonth()
            : $start->copy()->addYear();

        return $start->format('M Y') . ' – ' . $end->format('M Y');
    }

    /** Most recent payment as ['amount'=>float,'date'=>string], or null. */
    public function lastPayment(): ?array
    {
        if ($this->payments === []) {
            return null;
        }
        $sorted = $this->payments;
        usort($sorted, fn ($a, $b) => strcmp($b['date'], $a['date']));
        return ['amount' => $sorted[0]['amount'], 'date' => $sorted[0]['date'], 'dateLabel' => $sorted[0]['dateLabel']];
    }

    /** Sum of payment amounts in the current calendar year. */
    public function paidThisYear(): float
    {
        $year = (string) now()->year;
        return array_sum(array_map(
            fn ($p) => str_starts_with($p['date'], $year) ? $p['amount'] : 0.0,
            $this->payments
        ));
    }

    /** Sum of all payment amounts. */
    public function paidAllTime(): float
    {
        return array_sum(array_column($this->payments, 'amount'));
    }

    /** ISO datetime to "YYYY-MM-DD", or '' if unparseable. */
    private function isoToDate(string $iso): string
    {
        if ($iso === '') {
            return '';
        }
        try {
            return Carbon::parse($iso)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }
}
