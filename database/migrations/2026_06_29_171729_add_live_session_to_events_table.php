<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('run_duration')->default(60)->after('banner');
            $table->unsignedBigInteger('live_rider_id')->nullable()->after('run_duration');
            $table->tinyInteger('live_run_number')->nullable()->after('live_rider_id');
            $table->string('live_phase', 16)->nullable()->after('live_run_number');
            $table->timestamp('live_started_at')->nullable()->after('live_phase');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['run_duration', 'live_rider_id', 'live_run_number', 'live_phase', 'live_started_at']);
        });
    }
};
