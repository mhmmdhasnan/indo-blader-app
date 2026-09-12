<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_divisions', function (Blueprint $table) {
            $table->boolean('best_trick_active')->default(false)->after('live_stage');
        });
    }

    public function down(): void
    {
        Schema::table('event_divisions', function (Blueprint $table) {
            $table->dropColumn('best_trick_active');
        });
    }
};
