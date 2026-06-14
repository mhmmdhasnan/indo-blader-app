<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualification_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qualification_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rider_a_registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('rider_b_registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('trick_id')->nullable()->constrained('tricks')->nullOnDelete();
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED'])->default('PENDING');
            $table->foreignId('winner_registration_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualification_matches');
    }
};
