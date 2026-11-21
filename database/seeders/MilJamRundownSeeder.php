<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

/**
 * MIL JAM Asia Vol III 2026 (28 Nov 2026, The Forum Sport Hub Bintaro) — rundown acara untuk tab SCHEDULE
 * di halaman event, diambil dari "Rundown MIL Jam Asia 2026.xlsx".
 *
 * Hanya mengisi kolom `events.schedule`; kolom lain (rules, venue, dll.) tidak disentuh. Catatan internal
 * crew di Excel (PIC, kebutuhan alat, "Prepare", pembagian hadiah per presenter) sengaja tidak dimasukkan
 * karena halaman ini publik. Aman dijalankan ulang (schedule selalu ditimpa dengan isi yang sama).
 *
 * Event: slug "milf-2026" (bisa diganti lewat env MIL_JAM_SLUG).
 *
 *   php artisan db:seed --class=MilJamRundownSeeder --force
 */
class MilJamRundownSeeder extends Seeder
{
    private const EVENT_SLUG = 'milf-2026';
    private const DATE       = '2026-11-28';

    /** [time, title, tag] */
    private const ITEMS = [
        ['06:00', 'Pemanasan & Registrasi (cek nomor start, pakai full protector)',                       'ALL'],
        ['06:30', 'Pembukaan oleh MC — doa, opening speech, info kategori & aturan, foto bersama + video tagline', 'ALL'],
        ['07:00', 'Skate Cross U9 & U15 (Boys / Girls) — penyisihan, final 2 lap & pembagian hadiah', 'SKATE CROSS'],
        ['08:30', 'Registrasi ulang kategori Aggressive Inline Skate',                                     'AGGRESSIVE'],
        ['09:30', 'Pengenalan produk sponsor — Rintik Skin Care & Cougar Skate',                           'ALL'],
        ['09:35', 'Pembukaan kategori Aggressive Inline Skate — speech MiLF, foto bersama & video tagline', 'AGGRESSIVE'],
        ['09:40', 'Aggressive Inline — U9, Beginner Women, Beginner Men (+ final)',                        'AGGRESSIVE'],
        ['11:40', 'Ishoma & kuis',                                                                         'ALL'],
        ['13:00', 'Aggressive Inline — Open Women, Open Men (+ final)',                                    'AGGRESSIVE'],
        ['15:00', 'Aggressive Inline — MVP / Street PRO Men (+ final)',                                    'AGGRESSIVE'],
        ['16:00', 'Kelas Veteran',                                                                         'AGGRESSIVE'],
        ['17:00', 'Cash for Trick',                                                                        'AGGRESSIVE'],
        ['18:00', 'Ishoma & kuis',                                                                         'ALL'],
        ['18:30', 'Pengumuman & pembagian hadiah pemenang per kategori (termasuk Best Trick & Best Slam)', 'ALL'],
        ['19:30', 'Penutupan oleh MC — ucapan terima kasih sponsor, media partner & tenant',               'ALL'],
        ['20:00', 'Foto bersama (sesi pengawetan)',                                                        'ALL'],
    ];

    public function run(): void
    {
        $event = $this->findEvent();
        if (!$event) {
            return;
        }

        $event->schedule = array_map(fn (array $row) => [
            'date'  => self::DATE,
            'time'  => $row[0],
            'title' => $row[1],
            'tag'   => $row[2],
        ], self::ITEMS);
        $event->save();

        $this->command?->info(sprintf('Schedule "%s" (%s) diisi: %d item.', $event->title, $event->slug, count(self::ITEMS)));
    }

    private function findEvent(): ?Event
    {
        $slug  = env('MIL_JAM_SLUG', self::EVENT_SLUG);
        $event = Event::where('slug', $slug)->first();

        if (!$event) {
            $this->command?->error("Event dengan slug \"{$slug}\" tidak ditemukan.");
            $this->command?->line('Event yang ada: ' . Event::pluck('slug')->implode(', '));
        }

        return $event;
    }
}
