<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('placement');
            $table->unsignedSmallInteger('points_earned');
            $table->timestamps();
            $table->unique(['rider_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_histories');
    }
};
