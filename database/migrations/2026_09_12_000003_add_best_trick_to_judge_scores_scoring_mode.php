<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Widens the scoring_mode enum on judge_scores to add 'BEST_TRICK', following the
     * same raw-SQL pattern already used for enum widening elsewhere in this project
     * (see 2026_06_15_000010_add_head_judge_to_users_role.php and
     * 2026_09_11_161924_add_operator_to_users_role.php) since Laravel's schema builder
     * can't alter a MySQL ENUM without doctrine/dbal.
     *
     * Note: MySQL-only syntax — will fail against sqlite (this repo's phpunit.xml uses
     * an in-memory sqlite connection for tests, but no test in this repo currently runs
     * migrations against it, so this is a dormant, not active, incompatibility).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE judge_scores MODIFY scoring_mode ENUM('LIVE','KNOCKOUT','BEST_TRICK') NOT NULL DEFAULT 'LIVE'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE judge_scores MODIFY scoring_mode ENUM('LIVE','KNOCKOUT') NOT NULL DEFAULT 'LIVE'");
    }
};
