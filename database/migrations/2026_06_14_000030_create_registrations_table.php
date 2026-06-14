<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('entry_code')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->date('dob');
            $table->string('city');
            $table->enum('stance', ['Regular', 'Goofy'])->default('Regular');
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['STREET', 'PARK', 'VERT', 'FLAT']);
            $table->enum('experience', ['Amateur', 'Semi-Pro', 'Pro'])->default('Amateur');
            $table->string('ec_name');
            $table->string('ec_phone');
            $table->string('ec_relation');
            $table->enum('payment_method', ['Transfer', 'E-Wallet', 'QRIS'])->default('Transfer');
            $table->string('payment_proof')->nullable();
            $table->enum('payment_status', ['UNPAID', 'PENDING', 'VERIFIED'])->default('PENDING');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
