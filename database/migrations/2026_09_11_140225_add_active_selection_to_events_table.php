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
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('active_division_id')->nullable()->after('live_started_at')
                ->constrained('event_divisions')->nullOnDelete();
            $table->foreignId('active_group_id')->nullable()->after('active_division_id')
                ->constrained('division_groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_division_id');
            $table->dropConstrainedForeignId('active_group_id');
        });
    }
};
