<?php

namespace Database\Seeders;

use App\Models\GalleryItem;
use App\Models\Sponsor;
use Illuminate\Database\Seeder;

class SponsorSeeder extends Seeder
{
    public function run(): void
    {
        $sponsors = ['GRIND HOUSE', 'ASPHALT CO.', 'ROLLING THUNDER', 'STREET DIVISION', 'WHEEL WORKS', 'APEX BEARINGS'];

        foreach ($sponsors as $name) {
            Sponsor::create(['name' => $name]);
        }

        $gallery = [
            ['type' => 'photo', 'height' => 'tall',  'label' => 'Action — Rail',  'caption' => 'Rama, 12-stair royale'],
            ['type' => 'video', 'height' => 'short', 'label' => 'Edit — Day 1',   'caption' => 'Nationals recap 03:42'],
            ['type' => 'photo', 'height' => 'mid',   'label' => 'Crowd',          'caption' => 'Senayan, packed out'],
            ['type' => 'photo', 'height' => 'mid',   'label' => 'Vert Air',       'caption' => 'Yoga, 540 mute'],
            ['type' => 'photo', 'height' => 'tall',  'label' => 'Portrait',       'caption' => 'Bagus, backstage'],
            ['type' => 'video', 'height' => 'mid',   'label' => 'Best Trick',     'caption' => 'Top 5 tricks 02:10'],
            ['type' => 'photo', 'height' => 'short', 'label' => 'Podium',         'caption' => 'Street Final podium'],
            ['type' => 'photo', 'height' => 'mid',   'label' => 'Park Flow',      'caption' => 'Reza, Canggu sunset'],
            ['type' => 'photo', 'height' => 'tall',  'label' => 'Grind',          'caption' => 'Arif, kink rail'],
            ['type' => 'video', 'height' => 'short', 'label' => 'Hype',           'caption' => 'Event trailer 00:58'],
            ['type' => 'photo', 'height' => 'mid',   'label' => 'Detail',         'caption' => 'Wheels & frames'],
            ['type' => 'photo', 'height' => 'short', 'label' => 'Flatland',       'caption' => 'Eka, footwork combo'],
        ];

        foreach ($gallery as $i => $item) {
            GalleryItem::create(array_merge($item, ['sort_order' => $i + 1]));
        }
    }
}
