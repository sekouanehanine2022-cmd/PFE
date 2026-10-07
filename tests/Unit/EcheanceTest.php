<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class EcheanceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_une_echeance_dans_sept_jours_est_correctement_calculee(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 10:00:00'));

        $dateFin = Carbon::parse('2026-10-14')->startOfDay();
        $joursRestants = (int) Carbon::today()->diffInDays($dateFin, false);

        $this->assertSame(7, $joursRestants);
    }
}
