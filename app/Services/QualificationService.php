<?php

namespace App\Services;

use App\Models\QualificationMatch;
use App\Models\QualificationRound;
use App\Models\Registration;

class QualificationService
{
    public function randomizePairings(QualificationRound $round): void
    {
        $registrations = Registration::where('event_id', $round->event_id)
            ->where('status', 'APPROVED')
            ->inRandomOrder()
            ->get();

        QualificationMatch::where('qualification_round_id', $round->id)
            ->where('status', 'PENDING')
            ->delete();

        $chunks = $registrations->chunk(2);

        foreach ($chunks as $pair) {
            if ($pair->count() === 2) {
                $match = QualificationMatch::create([
                    'qualification_round_id'  => $round->id,
                    'rider_a_registration_id' => $pair->first()->id,
                    'rider_b_registration_id' => $pair->last()->id,
                    'status'                  => 'PENDING',
                ]);

                NotificationService::send(
                    $pair->first(),
                    'match_assigned',
                    'Qualification Match Assigned',
                    "You have been paired in {$round->name}. Check the qualification page for details."
                );

                NotificationService::send(
                    $pair->last(),
                    'match_assigned',
                    'Qualification Match Assigned',
                    "You have been paired in {$round->name}. Check the qualification page for details."
                );
            }
        }

        $round->update(['pairing_type' => 'RANDOM', 'status' => 'IN_PROGRESS']);
    }

    public function setWinner(QualificationMatch $match, Registration $winner): void
    {
        $match->update([
            'winner_registration_id' => $winner->id,
            'status'                 => 'COMPLETED',
        ]);

        NotificationService::send(
            $winner,
            'qualification_passed',
            'Qualification Passed!',
            "Congratulations! You won your qualification match in {$match->round->name}."
        );
    }
}
