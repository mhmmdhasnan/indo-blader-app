<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('rider', 'admin', 'judge', 'head_judge', 'operator') DEFAULT 'rider'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('rider', 'admin', 'judge', 'head_judge') DEFAULT 'rider'");
    }
};
