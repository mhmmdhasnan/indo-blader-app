<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bracket_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bracket_id')->constrained()->cascadeOnDelete();
            $table->enum('round', ['QF', 'SF', 'F', 'UB_R1', 'UB_R2', 'LB_R1', 'LB_R2', 'LB_F', 'GF']);
            $table->unsignedTinyInteger('match_number')->default(1);
            $table->foreignId('rider_a_registration_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->foreignId('rider_b_registration_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->foreignId('trick_id')->nullable()->constrained('tricks')->nullOnDelete();
            $table->foreignId('winner_registration_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->decimal('score_a', 5, 1)->nullable();
            $table->decimal('score_b', 5, 1)->nullable();
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED'])->default('PENDING');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bracket_matches');
    }
};
