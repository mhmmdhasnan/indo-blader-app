<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);            // "Street Open", "Park Beginner"
            $table->string('discipline', 64)->nullable(); // STREET, PARK, VERT, FLAT
            $table->string('level', 64)->nullable();      // Beginner, Open, Pro
            $table->unsignedSmallInteger('slots')->default(32);
            $table->unsignedSmallInteger('filled')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('competition_category')
                ->constrained('event_divisions')->nullOnDelete();
        });

        Schema::table('brackets', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('competition_level')
                ->constrained('event_divisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('brackets', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
        Schema::dropIfExists('event_divisions');
    }
};
