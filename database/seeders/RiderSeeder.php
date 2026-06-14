<?php

namespace Database\Seeders;

use App\Models\Rider;
use Illuminate\Database\Seeder;

class RiderSeeder extends Seeder
{
    public function run(): void
    {
        $riders = [
            [
                'slug' => 'rama', 'name' => 'Rama Adhyaksa', 'nick' => 'THE MACHINE',
                'city' => 'Jakarta', 'age' => 24, 'category' => 'STREET', 'stance' => 'Regular',
                'points' => 9820, 'sponsor' => 'GRIND HOUSE',
                'wins' => 14, 'podiums' => 23, 'comps' => 41, 'best_score' => 96.4,
                'bio' => 'Two-time national street champion. Known for impossible cab royales down 12-stairs.',
                'achievements' => ['2025 Nationals — 1st', '2024 Street Wars — 1st', '2024 Nationals — 2nd', '2023 Park Jam — 3rd'],
                'ig' => '@rama.bladed', 'yt' => 'RamaTV', 'tt' => '@machine.wheels',
            ],
            [
                'slug' => 'bagus', 'name' => 'Bagus Pratama', 'nick' => 'GANGSAR',
                'city' => 'Bandung', 'age' => 22, 'category' => 'PARK', 'stance' => 'Goofy',
                'points' => 9410, 'sponsor' => 'ASPHALT CO.',
                'wins' => 11, 'podiums' => 19, 'comps' => 38, 'best_score' => 95.1,
                'bio' => 'Park technician. Flow lines for days, never the same run twice.',
                'achievements' => ['2025 Park Jam Bali — 1st', '2025 Nationals — 2nd', '2024 Vert Attack — 3rd'],
                'ig' => '@bagus.flow', 'yt' => 'GangsarPark', 'tt' => '@gangsar',
            ],
            [
                'slug' => 'yoga', 'name' => 'Yoga Saputra', 'nick' => 'AIRTIME',
                'city' => 'Surabaya', 'age' => 26, 'category' => 'VERT', 'stance' => 'Regular',
                'points' => 9120, 'sponsor' => 'ROLLING THUNDER',
                'wins' => 9, 'podiums' => 17, 'comps' => 35, 'best_score' => 94.8,
                'bio' => 'Highest air in the country. Vert specialist with a 540 mute on lock.',
                'achievements' => ['2025 Vert Attack — 1st', '2024 Vert Attack — 1st', '2025 Nationals — 4th'],
                'ig' => '@yoga.airtime', 'yt' => 'AirtimeYoga', 'tt' => '@airtime',
            ],
            [
                'slug' => 'dimas', 'name' => 'Dimas Wibowo', 'nick' => 'PROFESSOR',
                'city' => 'Yogyakarta', 'age' => 23, 'category' => 'STREET', 'stance' => 'Regular',
                'points' => 8870, 'sponsor' => 'STREET DIVISION',
                'wins' => 8, 'podiums' => 16, 'comps' => 33, 'best_score' => 94.2,
                'bio' => 'Technical wizard. Switch everything. Judges love the consistency.',
                'achievements' => ['2025 Best Trick — 1st', '2024 Street Wars — 2nd', '2025 Nationals — 5th'],
                'ig' => '@dimas.prof', 'yt' => 'ProfessorBlade', 'tt' => '@professor',
            ],
            [
                'slug' => 'reza', 'name' => 'Reza Maulana', 'nick' => 'BALI BULLET',
                'city' => 'Denpasar', 'age' => 21, 'category' => 'PARK', 'stance' => 'Goofy',
                'points' => 8540, 'sponsor' => 'WHEEL WORKS',
                'wins' => 7, 'podiums' => 14, 'comps' => 29, 'best_score' => 93.6,
                'bio' => 'Youngest in the top 10. Explosive park runs, fearless on transfers.',
                'achievements' => ['2025 Park Jam Bali — 2nd', '2024 Nationals — 6th'],
                'ig' => '@reza.bullet', 'yt' => 'BaliBullet', 'tt' => '@balibullet',
            ],
            [
                'slug' => 'arif', 'name' => 'Arif Setiawan', 'nick' => 'NORTHSIDE',
                'city' => 'Medan', 'age' => 27, 'category' => 'STREET', 'stance' => 'Goofy',
                'points' => 8200, 'sponsor' => 'APEX BEARINGS',
                'wins' => 6, 'podiums' => 13, 'comps' => 31, 'best_score' => 92.9,
                'bio' => 'Veteran grinder, owns the longest rail combos in the scene.',
                'achievements' => ['2024 Street Wars — 3rd', '2023 Nationals — 4th'],
                'ig' => '@arif.north', 'yt' => 'NorthsideAR', 'tt' => '@northside',
            ],
            [
                'slug' => 'galih', 'name' => 'Galih Nugroho', 'nick' => 'SPIN DR.',
                'city' => 'Semarang', 'age' => 25, 'category' => 'VERT', 'stance' => 'Regular',
                'points' => 7960, 'sponsor' => 'GRIND HOUSE',
                'wins' => 5, 'podiums' => 12, 'comps' => 28, 'best_score' => 92.1,
                'bio' => 'Rotation king. 720s on the mega ramp, all day.',
                'achievements' => ['2024 Vert Attack — 2nd', '2025 Nationals — 7th'],
                'ig' => '@galih.spin', 'yt' => 'SpinDoctor', 'tt' => '@spindr',
            ],
            [
                'slug' => 'fajar', 'name' => 'Fajar Ramadhan', 'nick' => 'SUNRISE',
                'city' => 'Makassar', 'age' => 20, 'category' => 'PARK', 'stance' => 'Regular',
                'points' => 7610, 'sponsor' => 'ASPHALT CO.',
                'wins' => 4, 'podiums' => 10, 'comps' => 24, 'best_score' => 91.4,
                'bio' => 'Rookie of the year. Smooth style well beyond his age.',
                'achievements' => ['2025 Rookie of the Year', '2025 Park Jam Bali — 4th'],
                'ig' => '@fajar.sunrise', 'yt' => 'SunriseFJ', 'tt' => '@sunrise',
            ],
            [
                'slug' => 'eka', 'name' => 'Eka Putra', 'nick' => 'CONCRETE',
                'city' => 'Malang', 'age' => 28, 'category' => 'FLAT', 'stance' => 'Regular',
                'points' => 7280, 'sponsor' => 'WHEEL WORKS',
                'wins' => 4, 'podiums' => 9, 'comps' => 26, 'best_score' => 90.8,
                'bio' => 'Flatland purist. Footwork combos nobody else attempts.',
                'achievements' => ['2025 Flatland Open — 1st', '2024 Flatland Open — 1st'],
                'ig' => '@eka.flat', 'yt' => 'ConcreteEka', 'tt' => '@concrete',
            ],
            [
                'slug' => 'bayu', 'name' => 'Bayu Anggara', 'nick' => 'GHOST',
                'city' => 'Bekasi', 'age' => 23, 'category' => 'STREET', 'stance' => 'Goofy',
                'points' => 7050, 'sponsor' => 'STREET DIVISION',
                'wins' => 3, 'podiums' => 8, 'comps' => 22, 'best_score' => 90.2,
                'bio' => 'Quiet assassin. Comes alive in finals, disappears in qualis.',
                'achievements' => ['2025 Street Wars — 4th', '2024 Nationals — 8th'],
                'ig' => '@bayu.ghost', 'yt' => 'GhostBayu', 'tt' => '@ghost',
            ],
        ];

        foreach ($riders as $rider) {
            Rider::create($rider);
        }
    }
}
