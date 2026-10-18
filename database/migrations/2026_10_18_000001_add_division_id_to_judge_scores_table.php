<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scores used to be keyed by (event, rider, run, stage, judge) only, so a rider
     * registered in several divisions had ONE shared set of scores — a later division
     * showed (and a reset deleted) the scores from another one. division_id scopes them.
     */
    public function up(): void
    {
        Schema::table('judge_scores', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('rider_id')
                ->constrained('event_divisions')->nullOnDelete();
            $table->index(['event_id', 'division_id', 'rider_id'], 'judge_scores_event_division_rider_index');
        });

        // Backfill the unambiguous rows: the rider is registered in exactly one division
        // of that event. Riders in several divisions are left NULL on purpose — which
        // division their old rows belong to can't be guessed here and is set by hand.
        DB::statement("
            UPDATE judge_scores js
            JOIN (
                SELECT ri.id AS rider_id, reg.event_id, MIN(reg.division_id) AS division_id
                FROM riders ri
                JOIN registrations reg ON reg.name = ri.name
                WHERE reg.division_id IS NOT NULL
                GROUP BY ri.id, reg.event_id
                HAVING COUNT(DISTINCT reg.division_id) = 1
            ) x ON x.rider_id = js.rider_id AND x.event_id = js.event_id
            SET js.division_id = x.division_id
            WHERE js.division_id IS NULL AND js.scoring_mode IN ('LIVE', 'BEST_TRICK')
        ");
    }

    public function down(): void
    {
        Schema::table('judge_scores', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropIndex('judge_scores_event_division_rider_index');
            $table->dropColumn('division_id');
        });
    }
};
