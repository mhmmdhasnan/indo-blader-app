<?php

namespace App\Http\Controllers;

use App\Models\EventDivision;
use App\Services\LiveScoreboardService;
use Illuminate\Http\Request;

/**
 * Halaman cetak "Print Heat": daftar rider per heat untuk satu divisi (heat sheet).
 * ?group=<id> hanya heat itu, tanpa parameter = semua heat tahap divisi sekarang.
 * ?stage=QUALIFICATION|FINAL menimpa tahap (default mengikuti divisi).
 */
class HeatSheetController extends Controller
{
    public function show(Request $request, EventDivision $division, LiveScoreboardService $scoreboard)
    {
        $division->load('event');
        $stage = in_array($request->query('stage'), ['QUALIFICATION', 'FINAL'], true)
            ? $request->query('stage')
            : ($division->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION');

        $groups = $division->groups()->where('stage', $stage)->orderBy('name')->get();
        if ($groupId = (int) $request->query('group')) {
            $groups = $groups->where('id', $groupId)->values();
            abort_if($groups->isEmpty(), 404);
        }

        // Tanpa group (mis. final satu kolam): satu heat tunggal berisi semua rider tahap itu.
        $heats = $groups->isEmpty()
            ? [['name' => $stage === 'FINAL' ? 'FINAL' : 'SEMUA RIDER', 'riders' => $scoreboard->registrationsForStage($division, $stage)->sortBy('name')->values()]]
            : $groups->map(fn ($g) => [
                'name'   => $g->name,
                'riders' => $scoreboard->registrationsForStage($division, $stage, $g->id)->sortBy('name')->values(),
            ])->all();

        $isJam    = $division->format === 'JAMMING';
        $criteria = $division->event?->criteriaFor('LIVE') ?? collect();

        return view('print.heat-sheet', compact('division', 'stage', 'heats', 'isJam', 'criteria'));
    }
}
