<?php

namespace Database\Seeders;

use App\Models\DivisionFinalist;
use App\Models\DivisionGroup;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventJudgeAssignment;
use App\Models\JudgeScore;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\ScoreDetail;
use App\Services\LiveScoreboardService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * HighFive Inline Cup 2026 — Mikayla Hartati Giles was never entered in Street AMATEUR OPEN WOMEN
 * (scored by hand on the day). Adds her registration + finalist slot and her three judged results
 * (every judge gets the same numbers, so the run averages are exactly 34.0 / 36.0 and the best-trick
 * bonus 4.0 → total 40.0).
 *
 * Her name/photo/city come from the Rider profile and registrations she already has in other
 * divisions — nothing is duplicated. Safe to re-run (everything is firstOrCreate/updateOrCreate).
 *
 *   php artisan db:seed --class=MikaylaAmateurWomenSeeder --force
 */
class MikaylaAmateurWomenSeeder extends Seeder
{
    private const EVENT_SLUG    = 'HIGHFIVE-2026';
    private const DIVISION_NAME = 'Street AMATEUR OPEN WOMEN';
    private const RIDER_NAME    = 'Mikayla Hartati Giles';

    private const RUN_1      = 34.0;
    private const RUN_2      = 36.0;
    private const BEST_TRICK = 4.0;

    public function run(): void
    {
        $event    = Event::where('slug', self::EVENT_SLUG)->firstOrFail();
        $division = EventDivision::where('event_id', $event->id)->where('name', self::DIVISION_NAME)->firstOrFail();
        $rider    = Rider::where('name', self::RIDER_NAME)->firstOrFail();

        // Personal data comes from her registration in another division of the same event.
        $source = Registration::where('event_id', $event->id)
            ->where('name', self::RIDER_NAME)
            ->where('division_id', '!=', $division->id)
            ->orderByDesc('id')
            ->firstOrFail();

        // The judges who already scored this division, so the panel is the same as everybody else's.
        $judgeIds = JudgeScore::where('division_id', $division->id)
            ->whereNotNull('judge_user_id')->distinct()->pluck('judge_user_id');
        if ($judgeIds->isEmpty()) {
            $judgeIds = EventJudgeAssignment::where('event_id', $event->id)->where('scoring_mode', 'LIVE')->pluck('user_id');
        }
        if ($judgeIds->isEmpty()) {
            $this->command?->error('Tidak ada judge untuk divisi ini — dibatalkan.');
            return;
        }

        DB::transaction(function () use ($event, $division, $rider, $source, $judgeIds) {
            $registration = Registration::where('event_id', $event->id)
                ->where('division_id', $division->id)
                ->where('name', self::RIDER_NAME)
                ->first();

            if (!$registration) {
                do {
                    $code = 'IB26-' . strtoupper(Str::random(5));
                } while (Registration::where('entry_code', $code)->exists());

                // Only columns this database really has (schema can differ slightly between environments).
                $copy = array_filter([
                    'user_id', 'name', 'email', 'phone', 'dob', 'city', 'stance', 'category', 'experience',
                    'ec_name', 'ec_phone', 'ec_relation', 'payment_method', 'payment_proof', 'photo', 'payment_status',
                ], fn ($column) => Schema::hasColumn('registrations', $column));

                $registration = Registration::create(array_merge(
                    $source->only($copy),
                    [
                        'entry_code'           => $code,
                        'event_id'             => $event->id,
                        'division_id'          => $division->id,
                        'division_group_id'    => DivisionGroup::where('event_division_id', $division->id)->orderBy('id')->value('id'),
                        'competition_category' => strtoupper(Str::after($division->name, 'Street ')),
                        'status'               => 'APPROVED',
                    ]
                ));

                EventDivision::withoutEvents(fn () => $division->increment('filled'));
                $this->command?->info("Pendaftaran dibuat: {$registration->entry_code}");
            }

            DivisionFinalist::firstOrCreate([
                'event_division_id' => $division->id,
                'registration_id'   => $registration->id,
            ]);

            // Observer only broadcasts a live-screen refresh — not wanted (or possible) from the CLI.
            JudgeScore::withoutEvents(function () use ($event, $division, $rider, $judgeIds) {
                foreach ($judgeIds as $judgeId) {
                    foreach ([1 => self::RUN_1, 2 => self::RUN_2] as $run => $total) {
                        $score = JudgeScore::updateOrCreate(
                            [
                                'judge_user_id' => $judgeId,
                                'event_id'      => $event->id,
                                'rider_id'      => $rider->id,
                                'division_id'   => $division->id,
                                'run_number'    => $run,
                                'scoring_mode'  => 'LIVE',
                                'live_stage'    => 'FINAL',
                            ],
                            ['total' => $total, 'status' => 'DONE']
                        );
                        ScoreDetail::updateOrCreate(
                            ['judge_score_id' => $score->id, 'criteria' => 'all'],
                            ['score' => $total]
                        );
                    }

                    JudgeScore::updateOrCreate(
                        [
                            'judge_user_id' => $judgeId,
                            'event_id'      => $event->id,
                            'rider_id'      => $rider->id,
                            'division_id'   => $division->id,
                            'run_number'    => 1,
                            'scoring_mode'  => 'BEST_TRICK',
                            'live_stage'    => 'FINAL',
                        ],
                        ['total' => self::BEST_TRICK, 'status' => 'DONE']
                    );
                }
            });
        });

        $rows = app(LiveScoreboardService::class)->buildLeaderboard($division, 'FINAL')->values();
        $pos  = $rows->search(fn ($r) => $r['rider']->id === $rider->id);
        $row  = $rows[$pos] ?? null;
        $this->command?->info(sprintf(
            '%s: run1 %s, run2 %s, total %s — peringkat %s dari %d (foto: %s)',
            self::RIDER_NAME, $row['run1'] ?? '-', $row['run2'] ?? '-', $row['total'] ?? '-',
            $pos === false ? '-' : $pos + 1, $rows->count(), $rider->avatar ? 'ada' : 'tidak ada'
        ));

        if ($division->fresh()->live_final_completed_at) {
            $this->command?->warn('Final divisi ini SUDAH diselesaikan: poin ranking Mikayla belum terhitung. '
                . 'Di panel admin tekan "Buka Ulang (Batalkan Poin)" lalu "Selesaikan Final" lagi.');
        }
    }
}
