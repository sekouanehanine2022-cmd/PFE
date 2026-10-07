<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PcPortableSeeder::class,
            MiniPcSeeder::class,
            EcranSeeder::class,
            ImprimanteSeeder::class,
            CableSeeder::class,
            ClavierSeeder::class,
            SourisSeeder::class,
            CasqueSeeder::class,
            AffectationSeeder::class,
        ]);
    }
}
