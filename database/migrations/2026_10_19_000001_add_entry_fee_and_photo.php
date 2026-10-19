<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // 0 = gratis (step payment di form register dilewati).
            $table->unsignedInteger('entry_fee')->default(0)->after('prize_hidden');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('payment_proof');
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn('entry_fee'));
        Schema::table('registrations', fn (Blueprint $t) => $t->dropColumn('photo'));
    }
};
