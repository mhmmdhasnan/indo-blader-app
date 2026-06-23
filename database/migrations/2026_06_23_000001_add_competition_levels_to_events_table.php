<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('competition_levels')->nullable()->after('categories');
        });

        // Change competition_category from enum to string
        DB::statement('ALTER TABLE registrations MODIFY COLUMN competition_category VARCHAR(64) NULL');
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('competition_levels');
        });

        DB::statement("ALTER TABLE registrations MODIFY COLUMN competition_category ENUM('Beginner','Open','Pro') NULL");
    }
};
