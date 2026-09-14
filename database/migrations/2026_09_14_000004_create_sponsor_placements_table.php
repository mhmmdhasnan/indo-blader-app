<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsor_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->string('screen'); // 'idle' | 'nextup'
            $table->decimal('x', 5, 2)->default(50); // % from left, banner center
            $table->decimal('y', 5, 2)->default(50); // % from top, banner center
            $table->timestamps();
            $table->unique(['sponsor_id', 'screen']);
        });

        // Seed default placements for sponsors that are already active, spread
        // out along the bottom of the idle screen and centered on next-up, so
        // nothing that was already showing disappears the moment this ships —
        // admin can drag them wherever they actually want afterwards.
        $sponsors = \DB::table('sponsors')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $count = $sponsors->count();
        $now = now();

        $idleRows = [];
        $nextupRows = [];
        foreach ($sponsors as $i => $sponsor) {
            $x = $count > 1 ? 15 + ($i * (70 / ($count - 1))) : 50;
            $idleRows[] = [
                'sponsor_id' => $sponsor->id, 'screen' => 'idle',
                'x' => round($x, 2), 'y' => 80,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        if ($sponsors->isNotEmpty()) {
            $nextupRows[] = [
                'sponsor_id' => $sponsors->first()->id, 'screen' => 'nextup',
                'x' => 50, 'y' => 82,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }

        if (!empty($idleRows)) {
            \DB::table('sponsor_placements')->insert($idleRows);
        }
        if (!empty($nextupRows)) {
            \DB::table('sponsor_placements')->insert($nextupRows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_placements');
    }
};
