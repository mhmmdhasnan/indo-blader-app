<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('schedule')->nullable()->after('blurb');
            $table->json('rules')->nullable()->after('schedule');
            $table->boolean('prize_hidden')->default(false)->after('prize');
            $table->decimal('latitude', 10, 7)->nullable()->after('venue');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['schedule', 'rules', 'prize_hidden', 'latitude', 'longitude']);
        });
    }
};
