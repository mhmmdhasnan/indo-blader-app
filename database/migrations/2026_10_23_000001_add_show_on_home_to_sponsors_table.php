<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buang kolom event_id dari percobaan sebelumnya (kalau sempat termigrasi).
        if (Schema::hasColumn('sponsors', 'event_id')) {
            Schema::table('sponsors', fn (Blueprint $table) => $table->dropConstrainedForeignId('event_id'));
        }

        Schema::table('sponsors', function (Blueprint $table) {
            $table->boolean('show_on_home')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn('show_on_home');
        });
    }
};
