<?php

namespace App\Services;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\Registration;
use Illuminate\Support\Collection;

class BracketService
{
    public function generateSingleElimination(Bracket $bracket, Collection $registrations): void
    {
        $count   = $registrations->count();
        $slots   = $this->nextPowerOfTwo($count);
        $rounds  = $this->roundsForSlots($slots);
        $seeded  = $registrations->shuffle()->values()->pad($slots, null);

        $firstRound         = $rounds[0];
        $matchesInFirstRound = $slots / 2;

        for ($i = 0; $i < $matchesInFirstRound; $i++) {
            $riderA = $seeded[$i * 2];
            $riderB = $seeded[$i * 2 + 1];

            $isBye     = ($riderA && !$riderB) || (!$riderA && $riderB);
            $byeWinner = $isBye ? ($riderA ?? $riderB) : null;

            BracketMatch::create([
                'bracket_id'               => $bracket->id,
                'round'                    => $firstRound,
                'match_number'             => $i + 1,
                'rider_a_registration_id'  => $riderA?->id,
                'rider_b_registration_id'  => $riderB?->id,
                'winner_registration_id'   => $byeWinner?->id,
                'status'                   => $isBye ? 'COMPLETED' : 'PENDING',
            ]);
        }

        foreach (array_slice($rounds, 1) as $round) {
            $matchCount = $this->matchCountForRound($round, $slots);
            for ($i = 0; $i < $matchCount; $i++) {
                BracketMatch::create([
                    'bracket_id'   => $bracket->id,
                    'round'        => $round,
                    'match_number' => $i + 1,
                    'status'       => 'PENDING',
                ]);
            }
        }

        $bracket->update(['status' => 'IN_PROGRESS']);

        NotificationService::sendToMany(
            $registrations,
            'playoff_started',
            'Playoffs Have Started!',
            "The playoff bracket for {$bracket->event->title} is now live. Check the bracket page."
        );
    }

    public function generateDoubleElimination(Bracket $bracket, Collection $registrations): void
    {
        $count  = $registrations->count();
        $slots  = $this->nextPowerOfTwo($count);
        $seeded = $registrations->shuffle()->values()->pad($slots, null);

        $ubMatchCount = $slots / 2;
        for ($i = 0; $i < $ubMatchCount; $i++) {
            $riderA = $seeded[$i * 2];
            $riderB = $seeded[$i * 2 + 1];
            $isBye  = ($riderA && !$riderB) || (!$riderA && $riderB);

            BracketMatch::create([
                'bracket_id'              => $bracket->id,
                'round'                   => 'UB_R1',
                'match_number'            => $i + 1,
                'rider_a_registration_id' => $riderA?->id,
                'rider_b_registration_id' => $riderB?->id,
                'winner_registration_id'  => $isBye ? ($riderA ?? $riderB)?->id : null,
                'status'                  => $isBye ? 'COMPLETED' : 'PENDING',
            ]);
        }

        $lbR1Count = $ubMatchCount / 2;
        for ($i = 0; $i < $lbR1Count; $i++) {
            BracketMatch::create(['bracket_id' => $bracket->id, 'round' => 'LB_R1', 'match_number' => $i + 1, 'status' => 'PENDING']);
        }
        for ($i = 0; $i < max(1, $lbR1Count / 2); $i++) {
            BracketMatch::create(['bracket_id' => $bracket->id, 'round' => 'UB_R2', 'match_number' => $i + 1, 'status' => 'PENDING']);
        }
        for ($i = 0; $i < max(1, $lbR1Count / 2); $i++) {
            BracketMatch::create(['bracket_id' => $bracket->id, 'round' => 'LB_R2', 'match_number' => $i + 1, 'status' => 'PENDING']);
        }
        BracketMatch::create(['bracket_id' => $bracket->id, 'round' => 'LB_F',  'match_number' => 1, 'status' => 'PENDING']);
        BracketMatch::create(['bracket_id' => $bracket->id, 'round' => 'GF',    'match_number' => 1, 'status' => 'PENDING']);

        $bracket->update(['status' => 'IN_PROGRESS']);

        NotificationService::sendToMany(
            $registrations,
            'playoff_started',
            'Playoffs Have Started!',
            "The double elimination bracket for {$bracket->event->title} is now live."
        );
    }

    public function advanceWinner(BracketMatch $match, Registration $winner): void
    {
        $match->update([
            'winner_registration_id' => $winner->id,
            'status'                 => 'COMPLETED',
        ]);

        NotificationService::send(
            $winner,
            'bracket_advanced',
            'You Advanced!',
            "You won your {$match->round} match. Check the bracket for your next opponent."
        );

        $nextRound = $this->nextRoundFor($match->round);
        if (!$nextRound) return;

        $nextMatchNumber = (int) ceil($match->match_number / 2);
        $nextMatch = BracketMatch::where('bracket_id', $match->bracket_id)
            ->where('round', $nextRound)
            ->where('match_number', $nextMatchNumber)
            ->first();

        if (!$nextMatch) return;

        $slotA = $match->match_number % 2 === 1;
        $nextMatch->update([
            $slotA ? 'rider_a_registration_id' : 'rider_b_registration_id' => $winner->id,
        ]);
    }

    public function advanceWinnerDoubleElim(BracketMatch $match, Registration $winner): void
    {
        $loserId = $match->rider_a_registration_id === $winner->id
            ? $match->rider_b_registration_id
            : $match->rider_a_registration_id;

        $loser = $loserId ? Registration::find($loserId) : null;

        $match->update(['winner_registration_id' => $winner->id, 'status' => 'COMPLETED']);

        $this->slotIntoNextRound($match, $winner);

        if (str_starts_with($match->round, 'UB_') && $loser) {
            $this->slotIntoLowerBracket($match, $loser);
        }

        NotificationService::send($winner, 'bracket_advanced', 'You Advanced!',
            "You won your {$match->round} match in the bracket.");
    }

    private function slotIntoNextRound(BracketMatch $match, Registration $winner): void
    {
        $nextRound = $this->nextRoundFor($match->round);
        if (!$nextRound) return;

        $nextMatchNumber = (int) ceil($match->match_number / 2);
        $nextMatch = BracketMatch::where('bracket_id', $match->bracket_id)
            ->where('round', $nextRound)
            ->where('match_number', $nextMatchNumber)
            ->first();

        if (!$nextMatch) return;

        $slotA = $match->match_number % 2 === 1;
        $nextMatch->update([
            $slotA ? 'rider_a_registration_id' : 'rider_b_registration_id' => $winner->id,
        ]);
    }

    private function slotIntoLowerBracket(BracketMatch $ubMatch, Registration $loser): void
    {
        $lbRound = match ($ubMatch->round) {
            'UB_R1' => 'LB_R1',
            'UB_R2' => 'LB_R2',
            default  => null,
        };

        if (!$lbRound) return;

        $lbMatchNumber = (int) ceil($ubMatch->match_number / 2);
        $lbMatch = BracketMatch::where('bracket_id', $ubMatch->bracket_id)
            ->where('round', $lbRound)
            ->where('match_number', $lbMatchNumber)
            ->first();

        if (!$lbMatch) return;

        $slotA = is_null($lbMatch->rider_a_registration_id);
        $lbMatch->update([
            $slotA ? 'rider_a_registration_id' : 'rider_b_registration_id' => $loser->id,
        ]);
    }

    private function nextRoundFor(string $round): ?string
    {
        return match ($round) {
            'QF'    => 'SF',
            'SF'    => 'F',
            'F'     => null,
            'UB_R1' => 'UB_R2',
            'UB_R2' => 'GF',
            'LB_R1' => 'LB_R2',
            'LB_R2' => 'LB_F',
            'LB_F'  => 'GF',
            'GF'    => null,
            default => null,
        };
    }

    private function nextPowerOfTwo(int $n): int
    {
        $p = 1;
        while ($p < $n) $p *= 2;
        return max(2, $p);
    }

    private function roundsForSlots(int $slots): array
    {
        return match (true) {
            $slots >= 8 => ['QF', 'SF', 'F'],
            $slots === 4 => ['SF', 'F'],
            default     => ['F'],
        };
    }

    private function matchCountForRound(string $round, int $totalSlots): int
    {
        return match ($round) {
            'QF'    => (int) ($totalSlots / 2),
            'SF'    => (int) ($totalSlots / 4),
            'F'     => 1,
            default => 1,
        };
    }
}
