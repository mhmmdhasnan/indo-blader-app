<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('rider', 'admin', 'judge', 'head_judge', 'operator', 'registrar') DEFAULT 'rider'");

        // Event yang boleh dilihat/dikelola Registrar — di-assign oleh Admin.
        Schema::create('event_registrar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrar');
        DB::statement("UPDATE users SET role = 'rider' WHERE role = 'registrar'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('rider', 'admin', 'judge', 'head_judge', 'operator') DEFAULT 'rider'");
    }
};
