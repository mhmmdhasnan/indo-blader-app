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
        Schema::table('event_divisions', function (Blueprint $table) {
            $table->string('live_stage', 16)->default('QUALIFICATION')->after('is_active');
            $table->timestamp('live_final_completed_at')->nullable()->after('live_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_divisions', function (Blueprint $table) {
            $table->dropColumn(['live_stage', 'live_final_completed_at']);
        });
    }
};
