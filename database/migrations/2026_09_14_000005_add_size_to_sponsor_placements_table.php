<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsor_placements', function (Blueprint $table) {
            // Percentage scale relative to that screen's base banner size (100 = default).
            $table->unsignedSmallInteger('size')->default(100)->after('y');
        });
    }

    public function down(): void
    {
        Schema::table('sponsor_placements', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
