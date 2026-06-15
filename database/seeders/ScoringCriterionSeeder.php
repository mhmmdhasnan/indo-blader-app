<?php

namespace Database\Seeders;

use App\Models\ScoringCriterion;
use Illuminate\Database\Seeder;

class ScoringCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            ['name' => 'Difficulty',  'key' => 'difficulty',  'display_order' => 1],
            ['name' => 'Technically', 'key' => 'technically', 'display_order' => 2],
            ['name' => 'Style',       'key' => 'style',       'display_order' => 3],
            ['name' => 'Creativity',  'key' => 'creativity',  'display_order' => 4],
        ];

        foreach ($criteria as $data) {
            ScoringCriterion::firstOrCreate(['key' => $data['key']], $data);
        }
    }
}
