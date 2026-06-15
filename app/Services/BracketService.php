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
        $count     = $registrations->count();
        $seeded    = $registrations->shuffle()->values();
        $prevPower = $this->prevPowerOfTwo($count);
        $numPrelim = $count - $prevPower; // number of play-in matches needed

        $mainRounds      = $this->roundsForSlots($prevPower);
        $firstMainRound  = $mainRounds[0];
        $mainFirstCount  = $prevPower / 2; // matches in first main round

        if ($numPrelim > 0) {
            // Bottom (numPrelim * 2) seeds play in PRELIM; top seeds get direct byes
            $directPlayers = $seeded->take($count - $numPrelim * 2);
            $prelimPlayers = $seeded->slice($count - $numPrelim * 2)->values();

            // PRELIM matches
            for ($i = 0; $i < $numPrelim; $i++) {
                BracketMatch::create([
                    'bracket_id'              => $bracket->id,
                    'round'                   => 'PRELIM',
                    'match_number'            => $i + 1,
                    'rider_a_registration_id' => $prelimPlayers[$i * 2]->id,
                    'rider_b_registration_id' => $prelimPlayers[$i * 2 + 1]->id,
                    'status'                  => 'PENDING',
                ]);
            }

            // First main-round matches
            // Matches 1..ceil(numPrelim/2) receive PRELIM winners; rest get direct players
            $numPrelimGroups = (int) ceil($numPrelim / 2);
            $directIdx       = 0;

            for ($i = 0; $i < $mainFirstCount; $i++) {
                $matchNum  = $i + 1;
                $needPrelA = ($i * 2 + 1) <= $numPrelim; // slot A awaits PRELIM winner
                $needPrelB = ($i * 2 + 2) <= $numPrelim; // slot B awaits PRELIM winner

                BracketMatch::create([
                    'bracket_id'              => $bracket->id,
                    'round'                   => $firstMainRound,
                    'match_number'            => $matchNum,
                    'rider_a_registration_id' => $needPrelA ? null : $directPlayers->get($directIdx++)?->id,
                    'rider_b_registration_id' => $needPrelB ? null : $directPlayers->get($directIdx++)?->id,
                    'status'                  => 'PENDING',
                ]);
            }
        } else {
            // Count is exactly a power of 2 — standard first round
            for ($i = 0; $i < $mainFirstCount; $i++) {
                BracketMatch::create([
                    'bracket_id'              => $bracket->id,
                    'round'                   => $firstMainRound,
                    'match_number'            => $i + 1,
                    'rider_a_registration_id' => $seeded[$i * 2]->id,
                    'rider_b_registration_id' => $seeded[$i * 2 + 1]->id,
                    'status'                  => 'PENDING',
                ]);
            }
        }

        // Subsequent rounds (SF, F …)
        $matchCnt = $mainFirstCount;
        foreach (array_slice($mainRounds, 1) as $round) {
            $matchCnt = (int) ($matchCnt / 2);
            for ($i = 0; $i < $matchCnt; $i++) {
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
        $count     = $registrations->count();
        $prevPower = $this->prevPowerOfTwo($count);
        $numPrelim = $count - $prevPower;
        $seeded    = $registrations->shuffle()->values();

        // PRELIM for non-power-of-2 counts
        if ($numPrelim > 0) {
            $directPlayers = $seeded->take($count - $numPrelim * 2);
            $prelimPlayers = $seeded->slice($count - $numPrelim * 2)->values();
            for ($i = 0; $i < $numPrelim; $i++) {
                BracketMatch::create([
                    'bracket_id'              => $bracket->id,
                    'round'                   => 'PRELIM',
                    'match_number'            => $i + 1,
                    'rider_a_registration_id' => $prelimPlayers[$i * 2]->id,
                    'rider_b_registration_id' => $prelimPlayers[$i * 2 + 1]->id,
                    'status'                  => 'PENDING',
                ]);
            }
            $seeded = $directPlayers->pad($prevPower, null);
        } else {
            $seeded = $seeded->pad($prevPower, null);
        }

        // UB_R1
        $ubR1Count = $prevPower / 2;
        for ($i = 0; $i < $ubR1Count; $i++) {
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

        // Remaining rounds based on prevPower
        foreach ($this->doubleElimRounds($ubR1Count) as $round => $matchCount) {
            for ($i = 1; $i <= $matchCount; $i++) {
                BracketMatch::create(['bracket_id' => $bracket->id, 'round' => $round, 'match_number' => $i, 'status' => 'PENDING']);
            }
        }

        $bracket->update(['status' => 'IN_PROGRESS']);

        NotificationService::sendToMany(
            $registrations,
            'playoff_started',
            'Playoffs Have Started!',
            "The double elimination bracket for {$bracket->event->title} is now live."
        );
    }

    // Returns all rounds AFTER UB_R1 for a double elimination bracket
    private function doubleElimRounds(int $ubR1Count): array
    {
        return match (true) {
            $ubR1Count >= 8 => [             // 16+ players
                'UB_R2' => $ubR1Count / 2,
                'UB_SF' => $ubR1Count / 4,
                'UB_F'  => 1,
                'LB_R1' => $ubR1Count / 2,
                'LB_R2' => $ubR1Count / 2,
                'LB_R3' => $ubR1Count / 4,
                'LB_R4' => $ubR1Count / 4,
                'LB_SF' => 1,
                'LB_F'  => 1,
                'GF'    => 1,
            ],
            $ubR1Count === 4 => [            // 8 players
                'UB_R2' => 2,
                'UB_F'  => 1,
                'LB_R1' => 2,
                'LB_R2' => 2,
                'LB_SF' => 1,
                'LB_F'  => 1,
                'GF'    => 1,
            ],
            $ubR1Count === 2 => [            // 4 players
                'UB_F'  => 1,
                'LB_R1' => 1,
                'LB_F'  => 1,
                'GF'    => 1,
            ],
            default => ['GF' => 1],          // 2 players
        };
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

        $nextRound = $match->round === 'PRELIM'
            ? $this->firstMainRound($match->bracket_id)
            : $this->nextRoundFor($match->round);

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

    public function resetWinner(BracketMatch $match): void
    {
        $winnerId = $match->winner_registration_id;
        $loserId  = $match->rider_a_registration_id === $winnerId
            ? $match->rider_b_registration_id
            : $match->rider_a_registration_id;

        // Clear winner from the next round slot
        $isDoubleElim = BracketMatch::where('bracket_id', $match->bracket_id)
            ->whereIn('round', ['UB_R1', 'UB_F', 'LB_R1', 'LB_F'])->exists();

        if ($isDoubleElim && str_starts_with($match->round, 'UB_')) {
            $nextRound = $this->deNextRound($match->round, $match->bracket_id, 'UB');
        } elseif ($isDoubleElim && (str_starts_with($match->round, 'LB_') || $match->round === 'GF')) {
            $nextRound = $this->deNextRound($match->round, $match->bracket_id, 'LB');
        } elseif ($match->round === 'PRELIM') {
            $nextRound = $this->firstMainRound($match->bracket_id);
        } else {
            $nextRound = $this->nextRoundFor($match->round);
        }

        if ($nextRound && $winnerId) {
            // Find next match containing the winner
            $nextMatch = BracketMatch::where('bracket_id', $match->bracket_id)
                ->where('round', $nextRound)
                ->where(function ($q) use ($winnerId) {
                    $q->where('rider_a_registration_id', $winnerId)
                      ->orWhere('rider_b_registration_id', $winnerId);
                })
                ->first();

            if ($nextMatch) {
                if ($nextMatch->rider_a_registration_id === $winnerId) {
                    $nextMatch->update(['rider_a_registration_id' => null]);
                } else {
                    $nextMatch->update(['rider_b_registration_id' => null]);
                }
            }
        }

        // For double elimination: also clear loser from lower bracket
        if (str_starts_with($match->round, 'UB_') && $loserId) {
            $lbRound = $this->ubLoserLBRound($match->round, $match->bracket_id);

            if ($lbRound) {
                // Find the LB match that has this loser slotted
                $lbMatch = BracketMatch::where('bracket_id', $match->bracket_id)
                    ->where('round', $lbRound)
                    ->where(function ($q) use ($loserId) {
                        $q->where('rider_a_registration_id', $loserId)
                          ->orWhere('rider_b_registration_id', $loserId);
                    })
                    ->first();

                if ($lbMatch) {
                    if ($lbMatch->rider_a_registration_id === $loserId) {
                        $lbMatch->update(['rider_a_registration_id' => null]);
                    } else {
                        $lbMatch->update(['rider_b_registration_id' => null]);
                    }
                }
            }
        }

        $match->update([
            'winner_registration_id' => null,
            'score_a'                => null,
            'score_b'                => null,
            'status'                 => 'PENDING',
        ]);
    }

    public function advanceWinnerDoubleElim(BracketMatch $match, Registration $winner): void
    {
        $loserId = $match->rider_a_registration_id === $winner->id
            ? $match->rider_b_registration_id
            : $match->rider_a_registration_id;
        $loser = $loserId ? Registration::find($loserId) : null;

        $match->update(['winner_registration_id' => $winner->id, 'status' => 'COMPLETED']);

        // Winner moves up (UB or LB progression)
        if (str_starts_with($match->round, 'UB_')) {
            $nextUB = $this->deNextRound($match->round, $match->bracket_id, 'UB');
            if ($nextUB) $this->slotPlayer($match, $winner, $nextUB);

            // Loser drops to lower bracket
            if ($loser) {
                $lbDrop = $this->ubLoserLBRound($match->round, $match->bracket_id);
                if ($lbDrop) $this->slotPlayer($match, $loser, $lbDrop);
            }
        } elseif (str_starts_with($match->round, 'LB_') || $match->round === 'GF') {
            $nextLB = $this->deNextRound($match->round, $match->bracket_id, 'LB');
            if ($nextLB) $this->slotPlayer($match, $winner, $nextLB);
        }

        NotificationService::send($winner, 'bracket_advanced', 'You Advanced!',
            "You won your {$match->round} match in the bracket.");
    }

    // Slot a player into a target round, computing the correct match number
    private function slotPlayer(BracketMatch $match, Registration $player, string $targetRound): void
    {
        $currentCount = BracketMatch::where('bracket_id', $match->bracket_id)
            ->where('round', $match->round)->count();
        $targetCount  = BracketMatch::where('bracket_id', $match->bracket_id)
            ->where('round', $targetRound)->count();

        // 2:1 → halve; 1:1 → same number
        $targetMatchNumber = $currentCount > $targetCount
            ? (int) ceil($match->match_number / 2)
            : $match->match_number;

        $targetMatch = BracketMatch::where('bracket_id', $match->bracket_id)
            ->where('round', $targetRound)
            ->where('match_number', $targetMatchNumber)
            ->first();

        if (!$targetMatch) return;

        // Fill first available slot
        $col = is_null($targetMatch->rider_a_registration_id)
            ? 'rider_a_registration_id'
            : 'rider_b_registration_id';
        $targetMatch->update([$col => $player->id]);
    }

    // Find next round in UB or LB progression (skips missing rounds)
    private function deNextRound(string $round, int $bracketId, string $bracket): ?string
    {
        $ubOrder = ['UB_R1', 'UB_R2', 'UB_SF', 'UB_F', 'GF'];
        $lbOrder = ['LB_R1', 'LB_R2', 'LB_R3', 'LB_R4', 'LB_SF', 'LB_F', 'GF'];

        $order = ($bracket === 'UB') ? $ubOrder : $lbOrder;
        $idx   = array_search($round, $order);
        if ($idx === false) return null;

        for ($i = $idx + 1; $i < count($order); $i++) {
            if (BracketMatch::where('bracket_id', $bracketId)->where('round', $order[$i])->exists()) {
                return $order[$i];
            }
        }
        return null;
    }

    // Which LB round does a UB loser drop into?
    private function ubLoserLBRound(string $ubRound, int $bracketId): ?string
    {
        // UB Final loser goes straight to LB Final (Valorant rule)
        if ($ubRound === 'UB_F') return 'LB_F';

        $map = ['UB_R1' => 'LB_R1', 'UB_R2' => 'LB_R2', 'UB_SF' => 'LB_R3'];
        $target = $map[$ubRound] ?? null;
        if (!$target) return null;

        return BracketMatch::where('bracket_id', $bracketId)->where('round', $target)->exists()
            ? $target
            : null;
    }

    private function nextRoundFor(string $round): ?string
    {
        return match ($round) {
            'QF'  => 'SF',
            'SF'  => 'F',
            'F'   => null,
            'GF'  => null,
            default => null,
        };
    }

    private function prevPowerOfTwo(int $n): int
    {
        $p = 1;
        while ($p * 2 <= $n) $p *= 2;
        return max(2, $p);
    }

    private function nextPowerOfTwo(int $n): int
    {
        $p = 1;
        while ($p < $n) $p *= 2;
        return max(2, $p);
    }

    private function firstMainRound(int $bracketId): string
    {
        foreach (['QF', 'SF', 'F'] as $round) {
            if (BracketMatch::where('bracket_id', $bracketId)->where('round', $round)->exists()) {
                return $round;
            }
        }
        return 'F';
    }

    private function roundsForSlots(int $slots): array
    {
        return match (true) {
            $slots >= 8 => ['QF', 'SF', 'F'],
            $slots >= 4 => ['SF', 'F'],
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
