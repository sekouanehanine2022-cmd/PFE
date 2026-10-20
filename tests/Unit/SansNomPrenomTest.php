<?php

namespace Tests\Unit;

use App\Rules\SansNomPrenom;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SansNomPrenomTest extends TestCase
{
    public static function exemples(): array
    {
        return [
            ['Elodie Martin', 'ELodie8!', false],
            ['Elodie Martin', 'Martin8!', false],
            ['Elodie Martin', 'Nouveau8!', true],
            ["\u{00C9}lodie Martin", 'Elodie8!', false],
            ['Jean-Pierre Martin', 'Pierre8!', false],
        ];
    }

    #[DataProvider('exemples')]
    public function test_la_regle_ignore_la_casse_et_les_accents(string $nom, string $motDePasse, bool $attendu): void
    {
        $refuse = false;
        (new SansNomPrenom($nom))->validate('password', $motDePasse, function () use (&$refuse) { $refuse = true; });
        $this->assertSame($attendu, ! $refuse);
    }
}
