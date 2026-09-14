<?php

namespace App\Services;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\EventDivision;
use App\Models\Ranking;
use App\Models\RankingHistory;
use App\Models\Registration;
use App\Models\Rider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RankingService
{
    const POINTS = [
        1 => 100,
        2 => 80,
        3 => 60,
        4 => 60,
        5 => 40,
        6 => 40,
        7 => 40,
        8 => 40,
    ];

    public function calculateForBracket(Bracket $bracket): void
    {
        $event      = $bracket->event;
        $placements = $this->derivePlacements($bracket);

        foreach ($placements as $placement => $registrationIds) {
            foreach ($registrationIds as $regId) {
                $reg = Registration::find($regId);
                if (!$reg) continue;

                $rider = Rider::where('name', $reg->name)->first();
                if (!$rider) continue;

                $points = self::POINTS[$placement] ?? 20;

                RankingHistory::updateOrCreate(
                    ['rider_id' => $rider->id, 'event_id' => $event->id],
                    ['placement' => $placement, 'points_earned' => $points]
                );

                $rider->increment('points', $points);

                if ($placement === 1) {
                    $rider->increment('wins');
                }
                if ($placement <= 3) {
                    $rider->increment('podiums');
                }

                NotificationService::send(
                    $reg,
                    'score_published',
                    'Tournament Results Published',
                    "Your final placement: #{$placement} — {$points} ranking points awarded."
                );
            }
        }

        $this->rebuildRankingsTable();
    }

    public function calculateForLiveFinal(EventDivision $division): void
    {
        $event       = $division->event;
        $leaderboard = app(LiveScoreboardService::class)->buildLeaderboard($division, 'FINAL');
        $placements  = $this->deriveLiveFinalPlacements($leaderboard);

        foreach ($placements as $placement => $rows) {
            foreach ($rows as $row) {
                $rider = $row['rider'];
                $reg   = $row['registration'];
                if (!$rider) continue;

                $points = self::POINTS[$placement] ?? 20;

                RankingHistory::updateOrCreate(
                    ['rider_id' => $rider->id, 'event_id' => $event->id],
                    ['placement' => $placement, 'points_earned' => $points]
                );

                $rider->increment('points', $points);

                if ($placement === 1) {
                    $rider->increment('wins');
                }
                if ($placement <= 3) {
                    $rider->increment('podiums');
                }

                if ($reg) {
                    NotificationService::send(
                        $reg,
                        'score_published',
                        'Final Results Published',
                        "Your final placement in {$division->name}: #{$placement} — {$points} ranking points awarded."
                    );
                }
            }
        }

        $division->update(['live_final_completed_at' => now()]);

        $this->rebuildRankingsTable();
    }

    /**
     * Undo calculateForLiveFinal() for a division that was already completed —
     * reverses every point/win/podium it awarded (using the stored RankingHistory
     * rows, not a recalculation, so it exactly cancels what was actually given)
     * and deletes those history rows, then clears live_final_completed_at /
     * final_announced_at so the division can safely go back to QUALIFICATION and
     * be completed again later without double-counting.
     */
    public function revertLiveFinal(EventDivision $division): void
    {
        if (!$division->live_final_completed_at) {
            return;
        }

        $event    = $division->event;
        $riderIds = app(LiveScoreboardService::class)->buildLeaderboard($division, 'FINAL')
            ->pluck('rider.id')
            ->filter()
            ->values();

        DB::transaction(function () use ($event, $riderIds, $division) {
            $histories = RankingHistory::where('event_id', $event->id)
                ->whereIn('rider_id', $riderIds)
                ->get();

            foreach ($histories as $history) {
                $rider = Rider::find($history->rider_id);
                if ($rider) {
                    $rider->decrement('points', $history->points_earned);
                    if ($history->placement === 1) {
                        $rider->decrement('wins');
                    }
                    if ($history->placement <= 3) {
                        $rider->decrement('podiums');
                    }
                }
                $history->delete();
            }

            $division->update([
                'live_final_completed_at' => null,
                'final_announced_at'      => null,
            ]);
        });

        $this->rebuildRankingsTable();
    }

    private function deriveLiveFinalPlacements(Collection $leaderboard): array
    {
        $placements = [];
        $rank       = 0;
        $index      = 0;
        $prevBest   = null;

        foreach ($leaderboard as $row) {
            $index++;
            if ($prevBest === null || $row['total'] < $prevBest) {
                $rank = $index; // standard "1224" competition ranking: ties share a rank
            }
            $placements[$rank][] = $row;
            $prevBest = $row['total'];
        }

        return $placements;
    }

    public function rebuildRankingsTable(): void
    {
        $totals = RankingHistory::selectRaw('rider_id, SUM(points_earned) as total')
            ->groupBy('rider_id')
            ->orderByDesc('total')
            ->get();

        foreach ($totals as $i => $row) {
            Ranking::updateOrCreate(
                ['rider_id' => $row->rider_id],
                [
                    'total_points'  => $row->total,
                    'national_rank' => $i + 1,
                    'updated_at'    => now(),
                ]
            );
        }
    }

    private function derivePlacements(Bracket $bracket): array
    {
        $placements = [];

        $final = BracketMatch::where('bracket_id', $bracket->id)
            ->whereIn('round', ['F', 'GF'])
            ->first();

        if ($final && $final->winner_registration_id) {
            $placements[1][] = $final->winner_registration_id;
            $loserId = $final->rider_a_registration_id === $final->winner_registration_id
                ? $final->rider_b_registration_id
                : $final->rider_a_registration_id;
            if ($loserId) $placements[2][] = $loserId;
        }

        $sfMatches = BracketMatch::where('bracket_id', $bracket->id)
            ->whereIn('round', ['SF', 'LB_F'])
            ->get();

        foreach ($sfMatches as $sf) {
            if ($sf->winner_registration_id) {
                $loserId = $sf->rider_a_registration_id === $sf->winner_registration_id
                    ? $sf->rider_b_registration_id
                    : $sf->rider_a_registration_id;
                if ($loserId) $placements[3][] = $loserId;
            }
        }

        $qfMatches = BracketMatch::where('bracket_id', $bracket->id)
            ->whereIn('round', ['QF', 'UB_R1'])
            ->get();

        foreach ($qfMatches as $qf) {
            if ($qf->winner_registration_id) {
                $loserId = $qf->rider_a_registration_id === $qf->winner_registration_id
                    ? $qf->rider_b_registration_id
                    : $qf->rider_a_registration_id;
                if ($loserId) $placements[5][] = $loserId;
            }
        }

        return $placements;
    }
}
