<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_scoring_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scoring_criterion_id')->constrained('scoring_criteria')->cascadeOnDelete();
            $table->enum('applies_to', ['LIVE', 'KNOCKOUT', 'BOTH'])->default('BOTH');
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['event_id', 'scoring_criterion_id', 'applies_to'], 'esc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_scoring_criteria');
    }
};
