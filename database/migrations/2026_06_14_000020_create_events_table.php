<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('edition')->nullable();
            $table->string('city');
            $table->string('venue');
            $table->date('date');
            $table->string('date_label');
            $table->enum('status', ['OPEN', 'CLOSING', 'SOON', 'FULL', 'LIVE'])->default('SOON');
            $table->json('categories');
            $table->unsignedBigInteger('prize')->default(0);
            $table->unsignedSmallInteger('slots')->default(32);
            $table->unsignedSmallInteger('filled')->default(0);
            $table->boolean('featured')->default(false);
            $table->text('blurb')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
