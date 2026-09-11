<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE riders MODIFY COLUMN category ENUM('STREET','PARK','VERT','FLAT','MINIRAMP') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE riders SET category = 'STREET' WHERE category = 'MINIRAMP'");
        DB::statement("ALTER TABLE riders MODIFY COLUMN category ENUM('STREET','PARK','VERT','FLAT') NOT NULL");
    }
};
