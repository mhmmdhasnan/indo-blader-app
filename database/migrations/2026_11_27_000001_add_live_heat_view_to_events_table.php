<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Tampilan "Semua heat" di layar publik /live: 'grid' (kotak per heat) atau 'gabung' (satu peringkat, 10 rider per halaman).
            $table->string('live_heat_view', 10)->default('grid')->after('live_duration');
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn('live_heat_view'));
    }
};
