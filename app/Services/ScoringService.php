<?php

namespace App\Services;

use App\Models\JudgeScore;
use App\Models\ScoreDetail;

class ScoringService
{
    public function calculateTotal(array $scores): float
    {
        $values = array_values(array_filter($scores, 'is_numeric'));
        if (empty($values)) return 0;
        return round((array_sum($values) / count($values)) * 10, 1);
    }

    public function submitScore(JudgeScore $score, array $criteria): void
    {
        $total = $this->calculateTotal($criteria);

        $score->update([
            'total'  => $total,
            'status' => 'DONE',
        ]);

        foreach ($criteria as $key => $value) {
            ScoreDetail::updateOrCreate(
                ['judge_score_id' => $score->id, 'criteria' => $key],
                ['score' => $value]
            );
        }
    }
}
