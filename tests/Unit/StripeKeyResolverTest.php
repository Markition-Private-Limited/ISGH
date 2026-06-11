<?php

namespace Tests\Unit;

use App\Services\StripeKeyResolver;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StripeKeyResolverTest extends TestCase
{
    private string $fixturePath = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Build a minimal in-memory xlsx with the same layout as the real
        // file: rows 1-2 blank, row 3 title, row 4 header, rows 5+ data.
        $book  = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A3', 'Stripe api key');
        $sheet->setCellValue('A4', 'Center');
        $sheet->setCellValue('B4', 'Production Key');
        $sheet->setCellValue('C4', 'Security Keys ');

        $rows = [
            ['Main Office',    'pk_live_MAINOFFICE', 'sk_live_MAINOFFICE'],
            ['Baytown',        'pk_live_BAYTOWN',    'sk_live_BAYTOWN'],
            ['Spring Branch',  'pk_live_SPRBRANCH',  'sk_live_SPRBRANCH'],
            ['Northshore',     'pk_live_NORTHSHORE', 'no need'],            // missing secret -> falls to default
            ['Hamza (Mission Bend)', 'pk_live_HAMZA', 'sk_live_HAMZA'],     // parentheticals stripped
        ];
        foreach ($rows as $i => $r) {
            $rowNum = 5 + $i;
            $sheet->setCellValue("A{$rowNum}", $r[0]);
            $sheet->setCellValue("B{$rowNum}", $r[1]);
            $sheet->setCellValue("C{$rowNum}", $r[2]);
        }

        $this->fixturePath = tempnam(sys_get_temp_dir(), 'stripe-keys-') . '.xlsx';
        (new Xlsx($book))->save($this->fixturePath);

        // Resolver caches by mtime+path md5; flush so each test starts clean.
        Cache::flush();

        config([
            'services.stripe.key'    => 'pk_test_ENV',
            'services.stripe.secret' => 'sk_test_ENV',
        ]);

        // Sheet-lookup logic only runs in production; pretend we're there so
        // the existing tests cover that codepath. The local-env short-circuit
        // is verified by test_local_env_always_returns_env_keys below.
        app()->detectEnvironment(fn () => 'production');
    }

    protected function tearDown(): void
    {
        if ($this->fixturePath && file_exists($this->fixturePath)) {
            @unlink($this->fixturePath);
        }
        parent::tearDown();
    }

    private function resolver(): StripeKeyResolver
    {
        return new StripeKeyResolver($this->fixturePath);
    }

    public function test_non_checkomatic_type_returns_env_keys(): void
    {
        $keys = $this->resolver()->resolve('Baytown', 'family');
        $this->assertSame('pk_test_ENV', $keys['publishable']);
        $this->assertSame('sk_test_ENV', $keys['secret']);
        $this->assertSame('env', $keys['source']);
    }

    public function test_exact_zone_match(): void
    {
        $keys = $this->resolver()->resolve('Baytown', 'checkomatic_individual');
        $this->assertSame('pk_live_BAYTOWN', $keys['publishable']);
        $this->assertSame('sk_live_BAYTOWN', $keys['secret']);
        $this->assertStringStartsWith('zone:', $keys['source']);
    }

    public function test_fuzzy_match_strips_islamic_center_suffix(): void
    {
        // Real WA values look like "Spring Branch Islamic Center" but the
        // sheet just says "Spring Branch". Normalization should bridge them.
        $keys = $this->resolver()->resolve('Spring Branch Islamic Center', 'checkomatic_family');
        $this->assertSame('pk_live_SPRBRANCH', $keys['publishable']);
        $this->assertSame('sk_live_SPRBRANCH', $keys['secret']);
    }

    public function test_fuzzy_match_strips_parentheticals(): void
    {
        // Sheet row name "Hamza (Mission Bend)" vs WA value "Hamza".
        $keys = $this->resolver()->resolve('Hamza', 'checkomatic_individual');
        $this->assertSame('pk_live_HAMZA', $keys['publishable']);
    }

    public function test_unknown_zone_falls_back_to_main_office(): void
    {
        $keys = $this->resolver()->resolve('Atlantis', 'checkomatic_family');
        $this->assertSame('pk_live_MAINOFFICE', $keys['publishable']);
        $this->assertSame('sk_live_MAINOFFICE', $keys['secret']);
        $this->assertStringContainsString('default:', $keys['source']);
    }

    public function test_missing_secret_in_row_falls_back_to_main_office(): void
    {
        // Northshore's secret is "no need" — the row is skipped during load,
        // so a lookup for Northshore drops to the Main Office row.
        $keys = $this->resolver()->resolve('Northshore', 'checkomatic_individual');
        $this->assertSame('pk_live_MAINOFFICE', $keys['publishable']);
        $this->assertSame('sk_live_MAINOFFICE', $keys['secret']);
    }

    public function test_missing_sheet_file_falls_back_to_env(): void
    {
        $resolver = new StripeKeyResolver('/path/that/does/not/exist.xlsx');
        $keys = $resolver->resolve('Baytown', 'checkomatic_individual');
        $this->assertSame('pk_test_ENV', $keys['publishable']);
        $this->assertSame('sk_test_ENV', $keys['secret']);
        $this->assertSame('env', $keys['source']);
    }

    public function test_null_zone_falls_back_to_main_office(): void
    {
        $keys = $this->resolver()->resolve(null, 'checkomatic_family');
        $this->assertSame('pk_live_MAINOFFICE', $keys['publishable']);
    }

    public function test_local_env_always_returns_env_keys(): void
    {
        // Non-production envs must never touch the sheet — even checkomatic
        // signups in local/staging should tokenize against the .env test keys
        // so they don't accidentally charge a real card or fail with a
        // live/test key mismatch.
        app()->detectEnvironment(fn () => 'local');

        $keys = $this->resolver()->resolve('Baytown', 'checkomatic_individual');
        $this->assertSame('pk_test_ENV', $keys['publishable']);
        $this->assertSame('sk_test_ENV', $keys['secret']);
        $this->assertSame('env', $keys['source']);
    }
}
