<?php

namespace App\Services;

use App\Models\DivisionFinalist;
use App\Models\DivisionGroup;
use App\Models\EventDivision;
use App\Models\JudgeScore;
use App\Models\Registration;
use App\Models\Rider;
use Illuminate\Support\Collection;

class LiveScoreboardService
{
    public function registrationsForStage(EventDivision $division, string $stage, ?int $groupId = null): Collection
    {
        if ($stage === 'FINAL') {
            // Final is always one combined pool, regardless of qualification groups.
            $regIds = DivisionFinalist::where('event_division_id', $division->id)->pluck('registration_id');
        } else {
            $regIds = Registration::where('division_id', $division->id)
                ->where('status', 'APPROVED')
                ->when($groupId, fn ($q) => $q->where('division_group_id', $groupId))
                ->pluck('id');
        }

        return Registration::whereIn('id', $regIds)->get();
    }

    /**
     * Read-only rider match — mirrors the lookup order used elsewhere
     * (e.g. Judge\Dashboard::resolveRiderIdFromRegistration), but never
     * creates a Rider row since this runs on every page render / poll.
     */
    private function resolveExistingRiderId(Registration $reg): ?int
    {
        if ($reg->user_id && $rider = Rider::where('user_id', $reg->user_id)->first()) {
            return $rider->id;
        }

        return Rider::where('name', $reg->name)->value('id');
    }

    /**
     * Best Trick bonus: judges type one direct 0-20 score per attempt (no
     * criteria breakdown), each attempt is averaged across judges, and the
     * highest of the (up to 3) per-attempt averages is the bonus added to the
     * rider's FINAL total. Returns 0 if the rider has no Best Trick scores yet.
     */
    private function bestTrickBonus(int $eventId, int $riderId): float
    {
        $attemptAverages = JudgeScore::where('event_id', $eventId)
            ->where('rider_id', $riderId)
            ->where('scoring_mode', 'BEST_TRICK')
            ->where('live_stage', 'FINAL')
            ->where('status', 'DONE')
            ->get()
            ->groupBy('run_number')
            ->map(fn ($attempt) => $attempt->avg('total'));

        return $attemptAverages->isEmpty() ? 0.0 : round($attemptAverages->max(), 1);
    }

    /**
     * Always returns one row per registered rider — even before any score is
     * submitted — so the public leaderboard can show the full roster (with
     * blank scores) instead of an empty state.
     */
    public function buildLeaderboard(EventDivision $division, string $stage, ?int $groupId = null): Collection
    {
        $registrations = $this->registrationsForStage($division, $stage, $groupId);

        $riderMap = $registrations
            ->mapWithKeys(fn ($reg) => [$this->resolveExistingRiderId($reg) => $reg])
            ->filter(fn ($reg, $riderId) => $riderId !== null);

        if ($riderMap->isEmpty()) {
            return collect();
        }

        $riders = Rider::whereIn('id', $riderMap->keys())->get()->keyBy('id');

        $scoresByRider = JudgeScore::where('event_id', $division->event_id)
            ->where('scoring_mode', 'LIVE')
            ->where('status', 'DONE')
            ->where('live_stage', $stage)
            ->whereIn('rider_id', $riderMap->keys())
            ->get()
            ->groupBy('rider_id');

        return $riderMap
            ->map(function ($reg, $riderId) use ($riders, $scoresByRider, $division, $stage) {
                $rider       = $riders->get($riderId);
                $riderScores = $scoresByRider->get($riderId, collect());
                $run1avg     = $riderScores->where('run_number', 1)->avg('total');
                $run2avg     = $riderScores->where('run_number', 2)->avg('total');
                $best        = max($run1avg ?? 0, $run2avg ?? 0);
                $bonus       = $stage === 'FINAL' ? $this->bestTrickBonus($division->event_id, $riderId) : 0.0;

                return [
                    'rider'            => $rider,
                    'registration'     => $reg,
                    'run1'             => $run1avg !== null ? round($run1avg, 1) : null,
                    'run2'             => $run2avg !== null ? round($run2avg, 1) : null,
                    'best'             => round($best, 1),
                    'best_trick_bonus' => $bonus,
                    'total'            => round($best + $bonus, 1),
                ];
            })
            ->filter(fn ($row) => $row['rider'] !== null)
            ->values()
            ->sort(fn ($a, $b) => $b['total'] <=> $a['total'] ?: strcmp($a['rider']->name, $b['rider']->name))
            ->values();
    }

    /**
     * Qualification leaderboard broken into sections — one per DivisionGroup
     * (heat), plus an "ungrouped" section for approved registrations not yet
     * assigned to a group. When the division has no groups at all, returns a
     * single section with group=null holding the full combined leaderboard.
     */
    public function buildQualificationSections(EventDivision $division): Collection
    {
        $groups = DivisionGroup::where('event_division_id', $division->id)->orderBy('name')->get();

        if ($groups->isEmpty()) {
            return collect([[
                'group'       => null,
                'leaderboard' => $this->buildLeaderboard($division, 'QUALIFICATION'),
            ]]);
        }

        $sections = $groups->map(fn ($group) => [
            'group'       => $group,
            'leaderboard' => $this->buildLeaderboard($division, 'QUALIFICATION', $group->id),
        ]);

        $ungroupedCount = Registration::where('division_id', $division->id)
            ->where('status', 'APPROVED')
            ->whereNull('division_group_id')
            ->count();

        if ($ungroupedCount > 0) {
            $sections->push([
                'group'       => null,
                'leaderboard' => $this->buildLeaderboard($division, 'QUALIFICATION')
                    ->filter(fn ($row) => $row['registration']->division_group_id === null)
                    ->values(),
            ]);
        }

        return $sections->values();
    }
}
