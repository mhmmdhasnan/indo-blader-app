<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RiderSeeder::class,
            EventSeeder::class,
            SponsorSeeder::class,
        ]);
    }
}
