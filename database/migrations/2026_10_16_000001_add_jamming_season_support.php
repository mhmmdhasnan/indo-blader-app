<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_divisions', function (Blueprint $table) {
            // STANDARD = rider per rider, JAMMING = 1 heat jalan serentak
            $table->string('format', 16)->default('STANDARD')->after('level');
            $table->unsignedInteger('jam_duration')->default(180)->after('format');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('live_jam')->default(false)->after('live_phase');
            $table->unsignedInteger('live_duration')->nullable()->after('live_jam');
        });
    }

    public function down(): void
    {
        Schema::table('event_divisions', function (Blueprint $table) {
            $table->dropColumn(['format', 'jam_duration']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['live_jam', 'live_duration']);
        });
    }
};
