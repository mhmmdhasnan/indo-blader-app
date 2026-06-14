<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judge_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('run_number')->default(1);
            $table->decimal('execution', 4, 1)->default(0);
            $table->decimal('style', 4, 1)->default(0);
            $table->decimal('creativity', 4, 1)->default(0);
            $table->decimal('difficulty', 4, 1)->default(0);
            $table->decimal('total', 5, 1)->default(0);
            $table->enum('status', ['WAITING', 'ON_COURSE', 'DONE'])->default('WAITING');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judge_scores');
    }
};
