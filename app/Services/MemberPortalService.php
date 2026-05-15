<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Orchestrates member-portal data: assembles the full WildApricot bundle
 * (contact + family + invoices + payments), caches it per-member, and
 * delegates profile writes back to WildApricotService.
 */
class MemberPortalService
{
    private const CACHE_TTL_MINUTES = 10;

    public function __construct(private WildApricotService $wa)
    {
    }

    /** Cache key for a member's assembled bundle. */
    private function cacheKey(int $contactId): string
    {
        return "member_portal_bundle_{$contactId}";
    }

    /**
     * Fetch every slice of a member's data and cache the assembled bundle.
     * Secondary-call failures degrade gracefully to empty slices.
     */
    public function assembleBundle(int $contactId): array
    {
        $contact  = $this->wa->getContactById($contactId) ?? [];
        $family   = $this->wa->getFamilyMembers($contactId);
        $invoices = $this->wa->getInvoicesForContact($contactId);
        $payments = $this->wa->getPaymentsForContact($contactId);

        $bundle = [
            'contact'  => $contact,
            'family'   => $family,
            'invoices' => $invoices,
            'payments' => $payments,
        ];

        Cache::put($this->cacheKey($contactId), $bundle, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $bundle;
    }

    /** Return the cached bundle, assembling (and caching) on a cache miss. */
    public function getBundle(int $contactId): array
    {
        $cached = Cache::get($this->cacheKey($contactId));
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }
        return $this->assembleBundle($contactId);
    }

    /** Drop the cached bundle so the next load re-fetches fresh data. */
    public function invalidate(int $contactId): void
    {
        Cache::forget($this->cacheKey($contactId));
    }

    /**
     * Persist primary-member profile edits to WildApricot, then invalidate cache.
     * @throws \RuntimeException on WA rejection.
     */
    public function updateProfile(int $contactId, array $data): array
    {
        $result = $this->wa->updateMember($contactId, $data);
        $this->invalidate($contactId);
        return $result;
    }

    /**
     * Persist a family-member (spouse) edit to WildApricot, then invalidate
     * the primary member's cache so the change shows on next load.
     * @throws \RuntimeException on WA rejection.
     */
    public function updateFamilyMember(int $primaryContactId, int $familyContactId, array $data): array
    {
        $result = $this->wa->updateMember($familyContactId, $data);
        $this->invalidate($primaryContactId);
        return $result;
    }
}
