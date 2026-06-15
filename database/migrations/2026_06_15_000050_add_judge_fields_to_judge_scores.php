<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judge_scores', function (Blueprint $table) {
            $table->foreignId('judge_user_id')->nullable()->constrained('users')->nullOnDelete()->after('id');
            $table->enum('scoring_mode', ['LIVE', 'KNOCKOUT'])->default('LIVE')->after('run_number');
        });
    }

    public function down(): void
    {
        Schema::table('judge_scores', function (Blueprint $table) {
            $table->dropForeign(['judge_user_id']);
            $table->dropColumn(['judge_user_id', 'scoring_mode']);
        });
    }
};
