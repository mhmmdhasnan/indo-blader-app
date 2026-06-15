<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add a plain index on judge_score_id so the FK can use it, then drop composite unique
        DB::statement('ALTER TABLE score_details ADD INDEX sd_jsi (judge_score_id)');
        DB::statement('ALTER TABLE score_details DROP INDEX score_details_judge_score_id_criteria_unique');
        DB::statement('ALTER TABLE score_details MODIFY COLUMN criteria VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE score_details ADD UNIQUE KEY score_details_unique (judge_score_id, criteria)');
        DB::statement('ALTER TABLE score_details DROP INDEX sd_jsi');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE score_details ADD INDEX sd_jsi (judge_score_id)');
        DB::statement('ALTER TABLE score_details DROP INDEX score_details_unique');
        DB::statement("ALTER TABLE score_details MODIFY COLUMN criteria ENUM('EXECUTION','STYLE','DIFFICULTY','CONSISTENCY','CREATIVITY') NOT NULL");
        DB::statement('ALTER TABLE score_details ADD UNIQUE KEY score_details_judge_score_id_criteria_unique (judge_score_id, criteria)');
        DB::statement('ALTER TABLE score_details DROP INDEX sd_jsi');
    }
};
