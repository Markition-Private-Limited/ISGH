<?php

namespace Tests\Unit;

use App\Services\LevelChangeService;
use App\Support\MemberProfile;
use Tests\TestCase;

class LevelChangeServiceTest extends TestCase
{
    private function profileWithLevel(string $levelName): MemberProfile
    {
        return new MemberProfile(['contact' => [
            'Id'              => 999,
            'MembershipLevel' => ['Id' => 1, 'Name' => $levelName],
            'FieldValues'     => [],
        ]]);
    }

    public function test_available_levels_excludes_current_type(): void
    {
        $svc = app(LevelChangeService::class);
        $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

        $types = array_column($levels, 'type');
        $this->assertNotContains('individual', $types, 'current type excluded');
        $this->assertCount(6, $levels);
    }

    public function test_available_levels_flag_family_and_checkomatic(): void
    {
        $svc = app(LevelChangeService::class);
        $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

        $byType = [];
        foreach ($levels as $l) {
            $byType[$l['type']] = $l;
        }

        $this->assertTrue($byType['family']['includesFamily']);
        $this->assertFalse($byType['lifetime_individual']['includesFamily']);
        $this->assertTrue($byType['checkomatic_individual']['isCheckomatic']);
        $this->assertArrayHasKey('fee', $byType['family']);
        $this->assertArrayHasKey('label', $byType['family']);
    }
}
