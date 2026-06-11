<?php

namespace App\Services;

use App\Support\MembershipTypes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Resolves the Stripe publishable + secret keys for a given (zone, membership type).
 *
 * Non-checkomatic types — and any error path — return the env-configured keys
 * (services.stripe.key / services.stripe.secret). Checkomatic types look the
 * member's zone up in storage/app/private/stripe/isgh Stripe keys.xlsx and
 * fall back to the sheet's "Main Office" row, then to the env keys, if a
 * zone-specific entry is missing or incomplete.
 *
 * The sheet is parsed once per file-mtime and cached forever; touching the
 * file invalidates the cache automatically.
 */
class StripeKeyResolver
{
    private const SHEET_RELATIVE_PATH = 'private/stripe/isgh Stripe keys.xlsx';
    private const DEFAULT_ROW_NAME    = 'Main Office';

    /** Words stripped from zone names during normalization so "Spring Branch Islamic Center" ↔ "Spring Branch". */
    private const NORMALIZE_STRIP_WORDS = ['islamic', 'center', 'isgh', 'masjid', 'mosque'];

    public function __construct(private ?string $sheetPath = null) {}

    /**
     * @return array{publishable: ?string, secret: ?string, source: string}
     */
    public function resolve(?string $zone, string $membershipType): array
    {
        $envKeys = [
            'publishable' => config('services.stripe.key'),
            'secret'      => config('services.stripe.secret'),
            'source'      => 'env',
        ];

        // Local/staging/test envs always use the .env test keys. The per-zone
        // sheet holds LIVE keys for production centers; routing test traffic
        // through them would either fail (live keys reject test cards) or, if
        // it worked, charge real cards. Only production fans out by zone.
        if (app()->environment() !== 'production') {
            return $envKeys;
        }

        if (! MembershipTypes::isCheckomatic($membershipType)) {
            return $envKeys;
        }

        $rows = $this->loadSheet();
        if ($rows === []) {
            return $envKeys;
        }

        $needle = $this->normalize($zone ?? '');
        $picked = $needle !== '' ? ($rows[$needle] ?? null) : null;

        if ($picked === null || empty($picked['publishable']) || empty($picked['secret'])) {
            $defaultKey = $this->normalize(self::DEFAULT_ROW_NAME);
            $picked = $rows[$defaultKey] ?? null;
            if ($picked === null || empty($picked['publishable']) || empty($picked['secret'])) {
                Log::warning('StripeKeyResolver: default Main Office row missing or incomplete, falling back to env keys', [
                    'zone' => $zone, 'type' => $membershipType,
                ]);
                return $envKeys;
            }
            return [
                'publishable' => $picked['publishable'],
                'secret'      => $picked['secret'],
                'source'      => 'default:' . $picked['name'],
            ];
        }

        return [
            'publishable' => $picked['publishable'],
            'secret'      => $picked['secret'],
            'source'      => 'zone:' . $picked['name'],
        ];
    }

    /**
     * @return array<string, array{publishable: string, secret: string, name: string}>
     *   keyed by normalized center name
     */
    private function loadSheet(): array
    {
        $path = $this->sheetPath ?? storage_path('app/' . self::SHEET_RELATIVE_PATH);
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $mtime = filemtime($path) ?: 0;
        return Cache::rememberForever("stripe-keys:{$mtime}:" . md5($path), function () use ($path) {
            try {
                $sheet = IOFactory::load($path)->getActiveSheet();
            } catch (Throwable $e) {
                Log::error('StripeKeyResolver: failed to load sheet', ['path' => $path, 'error' => $e->getMessage()]);
                return [];
            }

            $rows = [];
            foreach ($sheet->toArray() as $row) {
                $name = trim((string) ($row[0] ?? ''));
                $pk   = trim((string) ($row[1] ?? ''));
                $sk   = trim((string) ($row[2] ?? ''));

                if ($name === '' || ! str_starts_with($pk, 'pk_')) {
                    continue;
                }
                if (! str_starts_with($sk, 'sk_')) {
                    continue;
                }

                $key = $this->normalize($name);
                if ($key === '') {
                    continue;
                }
                $rows[$key] = ['publishable' => $pk, 'secret' => $sk, 'name' => $name];
            }

            Log::debug('StripeKeyResolver: loaded sheet', ['path' => $path, 'centers' => count($rows)]);
            return $rows;
        });
    }

    /**
     * Lowercase → strip parentheticals → strip suffix words → keep alphanumerics only.
     * Same function applied to sheet rows and the lookup needle so they collide.
     */
    private function normalize(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/\(.+?\)/', '', $value) ?? $value;
        foreach (self::NORMALIZE_STRIP_WORDS as $word) {
            $value = preg_replace('/\b' . preg_quote($word, '/') . '\b/', '', $value) ?? $value;
        }
        return preg_replace('/[^a-z0-9]/', '', $value) ?? '';
    }
}
