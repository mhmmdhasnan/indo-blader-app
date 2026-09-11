<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('judge_scores', function (Blueprint $table) {
            $table->string('live_stage', 16)->default('QUALIFICATION')->after('scoring_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('judge_scores', function (Blueprint $table) {
            $table->dropColumn('live_stage');
        });
    }
};
