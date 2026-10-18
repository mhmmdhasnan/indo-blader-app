<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A rider can finish several divisions of one event. The history used to be unique
     * per (rider, event), so the 2nd division overwrote the 1st one's row while
     * riders.points was still incremented for both — rankings and riders.points drifted
     * apart. One row per (rider, event, division) keeps them in sync.
     */
    public function up(): void
    {
        Schema::table('ranking_histories', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('event_id')
                ->constrained('event_divisions')->nullOnDelete();
            // New unique first: it also serves the rider_id FK, so the old one can be dropped.
            $table->unique(['rider_id', 'event_id', 'division_id'], 'ranking_histories_rider_event_division_unique');
        });

        Schema::table('ranking_histories', function (Blueprint $table) {
            $table->dropUnique('ranking_histories_rider_id_event_id_unique');
        });

        // Existing rows: only fill when the rider is registered in exactly one division of that event.
        DB::statement("
            UPDATE ranking_histories h
            JOIN (
                SELECT ri.id AS rider_id, reg.event_id, MIN(reg.division_id) AS division_id
                FROM riders ri
                JOIN registrations reg ON reg.name = ri.name
                WHERE reg.division_id IS NOT NULL
                GROUP BY ri.id, reg.event_id
                HAVING COUNT(DISTINCT reg.division_id) = 1
            ) x ON x.rider_id = h.rider_id AND x.event_id = h.event_id
            SET h.division_id = x.division_id
            WHERE h.division_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('ranking_histories', function (Blueprint $table) {
            $table->unique(['rider_id', 'event_id'], 'ranking_histories_rider_id_event_id_unique');
        });

        Schema::table('ranking_histories', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropUnique('ranking_histories_rider_event_division_unique');
            $table->dropColumn('division_id');
        });
    }
};
