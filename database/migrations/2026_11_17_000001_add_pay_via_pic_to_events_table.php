<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // true = biaya dibayar lewat PIC Provinsi, bukan upload bukti transfer di form register.
            $table->boolean('pay_via_pic')->default(false)->after('entry_fee');
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn('pay_via_pic'));
    }
};
