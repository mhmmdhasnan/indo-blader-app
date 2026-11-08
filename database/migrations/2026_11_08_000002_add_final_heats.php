<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Heat final: group biasa dengan stage = FINAL, dan tiap finalis menunjuk ke heat finalnya.
 * Group kualifikasi (stage default QUALIFICATION) dan Registration.division_group_id tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('division_groups', function (Blueprint $table) {
            $table->string('stage', 20)->default('QUALIFICATION')->after('name');
        });
        Schema::table('division_finalists', function (Blueprint $table) {
            $table->unsignedBigInteger('division_group_id')->nullable()->after('registration_id');
        });
    }

    public function down(): void
    {
        Schema::table('division_finalists', fn (Blueprint $t) => $t->dropColumn('division_group_id'));
        Schema::table('division_groups', fn (Blueprint $t) => $t->dropColumn('stage'));
    }
};
