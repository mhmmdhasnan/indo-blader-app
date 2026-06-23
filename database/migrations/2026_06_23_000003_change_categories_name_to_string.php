<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE categories MODIFY COLUMN name VARCHAR(64) NOT NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE categories MODIFY COLUMN name ENUM('Beginner','Open','Pro') NOT NULL");
    }
};
