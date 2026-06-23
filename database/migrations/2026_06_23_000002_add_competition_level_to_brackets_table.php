<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brackets', function (Blueprint $table) {
            $table->string('competition_level', 64)->nullable()->after('event_id');
        });
    }

    public function down(): void
    {
        Schema::table('brackets', function (Blueprint $table) {
            $table->dropColumn('competition_level');
        });
    }
};
