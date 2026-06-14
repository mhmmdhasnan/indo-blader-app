<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\JudgeScore;
use App\Models\Rider;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'slug' => 'nationals', 'title' => 'Indo Blader Nationals', 'edition' => 'Vol. 06',
                'city' => 'Jakarta', 'venue' => 'Senayan Plaza', 'date' => '2026-08-22',
                'date_label' => 'AUG 22–24, 2026', 'status' => 'OPEN',
                'categories' => ['STREET', 'PARK', 'VERT'], 'prize' => 250000000,
                'slots' => 64, 'filled' => 41, 'featured' => true,
                'blurb' => 'The biggest aggressive inline event in Southeast Asia. Three days, three disciplines, one champion.',
            ],
            [
                'slug' => 'streetwars', 'title' => 'Street Wars', 'edition' => 'Vol. 05',
                'city' => 'Bandung', 'venue' => 'Asia Afrika Blocks', 'date' => '2026-09-12',
                'date_label' => 'SEP 12, 2026', 'status' => 'OPEN',
                'categories' => ['STREET'], 'prize' => 80000000,
                'slots' => 32, 'filled' => 19, 'featured' => false,
                'blurb' => 'Pure street. Real spots, real concrete, no foam pits.',
            ],
            [
                'slug' => 'parkjam', 'title' => 'Park Jam Bali', 'edition' => 'Vol. 03',
                'city' => 'Denpasar', 'venue' => 'Canggu Skatepark', 'date' => '2026-10-04',
                'date_label' => 'OCT 4, 2026', 'status' => 'CLOSING',
                'categories' => ['PARK'], 'prize' => 60000000,
                'slots' => 24, 'filled' => 22, 'featured' => false,
                'blurb' => 'Sunset sessions on the island. Flow, transfers, vibes.',
            ],
            [
                'slug' => 'vertattack', 'title' => 'Vert Attack', 'edition' => 'Vol. 04',
                'city' => 'Surabaya', 'venue' => 'GOR Mega Ramp', 'date' => '2026-11-15',
                'date_label' => 'NOV 15, 2026', 'status' => 'SOON',
                'categories' => ['VERT'], 'prize' => 70000000,
                'slots' => 16, 'filled' => 6, 'featured' => false,
                'blurb' => 'Big air only. The mega ramp returns for round four.',
            ],
            [
                'slug' => 'besttrick', 'title' => 'Best Trick Battle', 'edition' => 'Vol. 02',
                'city' => 'Yogyakarta', 'venue' => 'Maliobo Drop', 'date' => '2026-12-06',
                'date_label' => 'DEC 6, 2026', 'status' => 'SOON',
                'categories' => ['STREET'], 'prize' => 40000000,
                'slots' => 24, 'filled' => 3, 'featured' => false,
                'blurb' => 'One rail. One stair set. Land the heaviest trick, take it all.',
            ],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }

        // Seed judge scores for nationals (live scoring data)
        $nationals = Event::where('slug', 'nationals')->first();
        $scoreData = [
            ['slug' => 'rama', 'run' => 1, 'exec' => 9.3, 'style' => 9.0, 'creativity' => 9.1, 'diff' => 9.0, 'status' => 'DONE'],
            ['slug' => 'rama', 'run' => 2, 'exec' => 9.5, 'style' => 9.6, 'creativity' => 9.7, 'diff' => 9.6, 'status' => 'DONE'],
            ['slug' => 'bagus', 'run' => 1, 'exec' => 9.1, 'style' => 9.2, 'creativity' => 9.0, 'diff' => 9.1, 'status' => 'DONE'],
            ['slug' => 'bagus', 'run' => 2, 'exec' => 9.4, 'style' => 9.3, 'creativity' => 9.2, 'diff' => 9.3, 'status' => 'DONE'],
            ['slug' => 'dimas', 'run' => 1, 'exec' => 8.9, 'style' => 8.7, 'creativity' => 8.8, 'diff' => 8.7, 'status' => 'DONE'],
            ['slug' => 'dimas', 'run' => 2, 'exec' => 9.4, 'style' => 9.1, 'creativity' => 9.6, 'diff' => 9.5, 'status' => 'ON_COURSE'],
            ['slug' => 'arif',  'run' => 1, 'exec' => 8.6, 'style' => 8.4, 'creativity' => 8.5, 'diff' => 8.3, 'status' => 'DONE'],
            ['slug' => 'arif',  'run' => 2, 'exec' => 8.9, 'style' => 8.8, 'creativity' => 9.0, 'diff' => 8.9, 'status' => 'DONE'],
            ['slug' => 'bayu',  'run' => 1, 'exec' => 8.7, 'style' => 8.8, 'creativity' => 8.8, 'diff' => 8.7, 'status' => 'DONE'],
            ['slug' => 'bayu',  'run' => 2, 'exec' => 8.6, 'style' => 8.5, 'creativity' => 8.7, 'diff' => 8.5, 'status' => 'DONE'],
            ['slug' => 'fajar', 'run' => 1, 'exec' => 8.4, 'style' => 8.5, 'creativity' => 8.3, 'diff' => 8.2, 'status' => 'DONE'],
            ['slug' => 'fajar', 'run' => 2, 'exec' => 8.9, 'style' => 8.8, 'creativity' => 8.8, 'diff' => 8.9, 'status' => 'DONE'],
        ];

        foreach ($scoreData as $s) {
            $rider = Rider::where('slug', $s['slug'])->first();
            if ($rider) {
                $total = (($s['exec'] + $s['style'] + $s['creativity'] + $s['diff']) / 4) * 10;
                JudgeScore::create([
                    'event_id'   => $nationals->id,
                    'rider_id'   => $rider->id,
                    'run_number' => $s['run'],
                    'execution'  => $s['exec'],
                    'style'      => $s['style'],
                    'creativity' => $s['creativity'],
                    'difficulty' => $s['diff'],
                    'total'      => round($total, 1),
                    'status'     => $s['status'],
                ]);
            }
        }
    }
}
