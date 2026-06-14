<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('nick');
            $table->string('city');
            $table->unsignedTinyInteger('age');
            $table->enum('category', ['STREET', 'PARK', 'VERT', 'FLAT']);
            $table->enum('stance', ['Regular', 'Goofy']);
            $table->unsignedInteger('points')->default(0);
            $table->string('sponsor')->nullable();
            $table->unsignedSmallInteger('wins')->default(0);
            $table->unsignedSmallInteger('podiums')->default(0);
            $table->unsignedSmallInteger('comps')->default(0);
            $table->decimal('best_score', 4, 1)->default(0);
            $table->text('bio')->nullable();
            $table->json('achievements')->nullable();
            $table->string('ig')->nullable();
            $table->string('yt')->nullable();
            $table->string('tt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
