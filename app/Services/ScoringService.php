<?php

namespace App\Services;

use App\Models\JudgeScore;
use App\Models\ScoreDetail;

class ScoringService
{
    public function calculateTotal(
        float $execution,
        float $style,
        float $creativity,
        float $difficulty,
        float $consistency
    ): float {
        return round((($execution + $style + $creativity + $difficulty + $consistency) / 5) * 10, 1);
    }

    public function submitScore(JudgeScore $score, array $criteria): void
    {
        $total = $this->calculateTotal(
            $criteria['execution'],
            $criteria['style'],
            $criteria['creativity'],
            $criteria['difficulty'],
            $criteria['consistency'],
        );

        $score->update([
            'execution'   => $criteria['execution'],
            'style'       => $criteria['style'],
            'creativity'  => $criteria['creativity'],
            'difficulty'  => $criteria['difficulty'],
            'consistency' => $criteria['consistency'],
            'total'       => $total,
            'status'      => 'DONE',
        ]);

        foreach ($criteria as $criteriaName => $value) {
            ScoreDetail::updateOrCreate(
                [
                    'judge_score_id' => $score->id,
                    'criteria'       => strtoupper($criteriaName),
                ],
                ['score' => $value]
            );
        }
    }
}
