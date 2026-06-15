<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            JudgeSeeder::class,
            ScoringCriterionSeeder::class,
            RiderSeeder::class,
            EventSeeder::class,
            SponsorSeeder::class,
        ]);
    }
}
