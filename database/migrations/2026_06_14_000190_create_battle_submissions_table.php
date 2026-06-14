<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battle_submissions', function (Blueprint $table) {
            $table->id();
            $table->enum('match_type', ['QUALIFICATION', 'PLAYOFF']);
            $table->unsignedBigInteger('match_id');
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('video_path');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'NEED_REUPLOAD'])->default('PENDING');
            $table->text('judge_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['match_type', 'match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_submissions');
    }
};
