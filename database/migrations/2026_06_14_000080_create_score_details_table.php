<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judge_score_id')->constrained()->cascadeOnDelete();
            $table->enum('criteria', ['EXECUTION', 'STYLE', 'DIFFICULTY', 'CONSISTENCY', 'CREATIVITY']);
            $table->decimal('score', 4, 1)->default(0);
            $table->timestamps();
            $table->unique(['judge_score_id', 'criteria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_details');
    }
};
