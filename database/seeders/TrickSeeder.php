<?php

namespace Database\Seeders;

use App\Models\Trick;
use Illuminate\Database\Seeder;

class TrickSeeder extends Seeder
{
    public function run(): void
    {
        $tricks = [
            ['name' => 'Soul',       'difficulty' => 'Easy',   'description' => 'Soul grind — front boot soul plate on the rail.'],
            ['name' => 'Royale',     'difficulty' => 'Easy',   'description' => 'Royale grind — both boots grinding with outside edges.'],
            ['name' => 'Makio',      'difficulty' => 'Easy',   'description' => 'Makio grind — back boot soul, front boot upright.'],
            ['name' => 'Backslide',  'difficulty' => 'Medium', 'description' => 'Backslide grind — grinding backwards on the soul plate.'],
            ['name' => 'Unity',      'difficulty' => 'Medium', 'description' => 'Unity grind — both boots on the same side.'],
            ['name' => 'Acid',       'difficulty' => 'Medium', 'description' => 'Acid soul grind — soul on the front boot, front foot flat.'],
            ['name' => 'Mizou',      'difficulty' => 'Hard',   'description' => 'Mizou grind — front boot soul, back foot angled outward.'],
            ['name' => 'Top Soul',   'difficulty' => 'Hard',   'description' => 'Top soul grind — grinding on the top of the soul plate.'],
            ['name' => 'Fishbrain',  'difficulty' => 'Expert', 'description' => 'Fishbrain grind — a torqued soul variation with a 180 approach.'],
        ];

        foreach ($tricks as $trick) {
            Trick::firstOrCreate(['name' => $trick['name']], $trick);
        }
    }
}
