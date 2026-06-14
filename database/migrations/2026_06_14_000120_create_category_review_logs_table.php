<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_category_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->foreignId('from_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('to_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_review_logs');
    }
};
