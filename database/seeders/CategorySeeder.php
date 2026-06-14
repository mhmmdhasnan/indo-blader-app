<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Beginner', 'description' => 'Entry level — for riders new to competition.'],
            ['name' => 'Open',     'description' => 'Intermediate level — open to all skill levels.'],
            ['name' => 'Pro',      'description' => 'Elite level — experienced competitive riders.'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
