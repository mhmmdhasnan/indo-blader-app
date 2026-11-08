<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan Operator (nomor run + rider) sebelum run dimulai. Disimpan di event supaya Head Judge /
 * Judge melihat pilihan yang sama (dicerminkan tiap poll), seperti active_division_id / active_group_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_run_number')->nullable()->after('active_group_id');
            $table->unsignedBigInteger('active_registration_id')->nullable()->after('active_run_number');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['active_run_number', 'active_registration_id']);
        });
    }
};
