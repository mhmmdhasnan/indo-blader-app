<?php

namespace App\Services;

use App\Events\LiveScoreUpdated;
use App\Models\DivisionFinalist;
use App\Models\DivisionGroup;
use App\Models\EventDivision;
use App\Models\JudgeScore;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Perpindahan fase sebuah divisi (Kualifikasi → Final → Best Trick). Dipakai bersama oleh
 * Admin dan Operator supaya aturannya satu tempat saja.
 */
class DivisionPhaseService
{
    /** Peringkat kualifikasi gabungan semua group — dasar pemilihan finalis. */
    public function qualificationRanking(EventDivision $division): Collection
    {
        return app(LiveScoreboardService::class)->buildLeaderboard($division, 'QUALIFICATION', null);
    }

    /** Id registrasi peringkat teratas (gabungan semua group). */
    public function topRegistrationIds(EventDivision $division, int $n): array
    {
        return $this->qualificationRanking($division)
            ->take(max(1, $n))
            ->map(fn ($row) => $row['registration']->id)
            ->all();
    }

    /**
     * Simpan finalis, bagi ke heat final, pindah ke FINAL, dan beri tahu finalis.
     *
     * @param  int[]  $registrationIds
     * @param  array<int,int>|null  $heatOf  pembagian manual: id registrasi => nomor heat (1..$heatCount). Null = otomatis (snake).
     */
    public function openFinal(EventDivision $division, array $registrationIds, int $heatCount, ?array $heatOf = null): void
    {
        foreach ($registrationIds as $regId) {
            DivisionFinalist::firstOrCreate([
                'event_division_id' => $division->id,
                'registration_id'   => $regId,
            ]);
        }
        DivisionFinalist::where('event_division_id', $division->id)
            ->whereNotIn('registration_id', $registrationIds)
            ->delete();

        $this->splitFinalHeats($division, $heatCount, $heatOf);

        $division->update(['live_stage' => 'FINAL']);
        LiveScoreUpdated::push($division->event_id);   // heat final bisa berubah tanpa live_stage berubah

        foreach (Registration::whereIn('id', $registrationIds)->get() as $reg) {
            NotificationService::send($reg, 'final_selected', 'Selamat, Anda Lolos Final!',
                "Anda terpilih sebagai finalis divisi {$division->name}. Final akan segera dimulai.");
        }
    }

    /**
     * Pembagian otomatis: finalis diurutkan menurut peringkat kualifikasi lalu dibagi dengan pola snake
     * (1→H, H→1, ...) supaya kekuatan tiap heat seimbang. Hasil: id registrasi => nomor heat (1..$heats).
     *
     * @param  int[]  $registrationIds
     * @return array<int,int>
     */
    public function autoAssignment(EventDivision $division, array $registrationIds, int $heats): array
    {
        $heats  = max(1, $heats);
        $rankOf = $this->qualificationRanking($division)
            ->values()->mapWithKeys(fn ($row, $i) => [$row['registration']->id => $i]);

        $ordered = collect($registrationIds)->map(fn ($id) => (int) $id)->unique()
            ->sortBy(fn ($id) => $rankOf[$id] ?? PHP_INT_MAX)->values();

        $map = [];
        foreach ($ordered as $i => $regId) {
            $round = intdiv($i, $heats);
            $pos   = $i % $heats;
            $map[$regId] = ($round % 2 === 0 ? $pos : $heats - 1 - $pos) + 1;
        }

        return $map;
    }

    /**
     * Bagi finalis ke heat final. $heatOf null = otomatis (snake); kalau diisi = pembagian manual
     * (id registrasi => nomor heat). Jumlah heat 1 = satu kolam gabungan (heat final lama dihapus).
     */
    public function splitFinalHeats(EventDivision $division, int $heatCount, ?array $heatOf = null): void
    {
        $finalists = DivisionFinalist::where('event_division_id', $division->id)->get();

        DivisionFinalist::where('event_division_id', $division->id)->update(['division_group_id' => null]);
        DivisionGroup::where('event_division_id', $division->id)->where('stage', 'FINAL')->delete();

        $heats = min(max(1, $heatCount), $finalists->count());
        if ($heats <= 1) return;

        $map = $heatOf !== null
            ? $heatOf
            : $this->autoAssignment($division, $finalists->pluck('registration_id')->all(), $heats);

        $groupIds = [];
        for ($h = 1; $h <= $heats; $h++) {
            $groupIds[$h] = DivisionGroup::create([
                'event_division_id' => $division->id,
                'name'              => 'Final Heat ' . $h,
                'stage'             => 'FINAL',
            ])->id;
        }

        foreach ($finalists as $finalist) {
            $heat = (int) ($map[$finalist->registration_id] ?? 0);
            $finalist->update(['division_group_id' => $groupIds[$heat] ?? null]);
        }
    }

    /**
     * Validasi pembagian manual: tiap finalis punya heat 1..$heats dan tidak ada heat yang kosong.
     * Mengembalikan pesan error, atau null kalau valid.
     *
     * @param  int[]  $registrationIds
     */
    public function validateManualAssignment(array $registrationIds, int $heats, array $heatOf): ?string
    {
        $heats = max(1, $heats);
        $used  = [];
        foreach ($registrationIds as $id) {
            $h = (int) ($heatOf[$id] ?? 0);
            if ($h < 1 || $h > $heats) return 'Tentukan heat (1–' . $heats . ') untuk setiap finalis.';
            $used[$h] = true;
        }
        for ($h = 1; $h <= $heats; $h++) {
            if (empty($used[$h])) return "Final Heat {$h} masih kosong. Isi minimal 1 finalis atau kurangi jumlah heat.";
        }

        return null;
    }

    /** Heat final divisi beserta finalisnya (untuk panel "Atur heat final"). */
    public function finalHeatOverview(EventDivision $division): Collection
    {
        $groups = DivisionGroup::where('event_division_id', $division->id)->where('stage', 'FINAL')->orderBy('name')->get();
        if ($groups->isEmpty()) return collect();

        $finalists = DivisionFinalist::where('event_division_id', $division->id)->with('registration')->get()->groupBy('division_group_id');

        return $groups->map(fn ($g) => [
            'group'     => $g,
            'finalists' => ($finalists[$g->id] ?? collect())->map(fn ($f) => $f->registration)->filter()->sortBy('name')->values(),
        ]);
    }

    /**
     * Pindahkan satu finalis ke heat final lain. Ditolak kalau finalis itu sudah punya nilai final
     * (nilainya sudah terikat ke heat sebelumnya). Mengembalikan pesan error, atau null kalau berhasil.
     */
    public function moveFinalist(EventDivision $division, int $registrationId, int $groupId): ?string
    {
        $finalist = DivisionFinalist::where('event_division_id', $division->id)->where('registration_id', $registrationId)->first();
        $target   = DivisionGroup::where('event_division_id', $division->id)->where('stage', 'FINAL')->find($groupId);
        if (!$finalist || !$target) return 'Finalis atau heat tujuan tidak valid.';
        if ((int) $finalist->division_group_id === (int) $target->id) return null;

        $row = app(LiveScoreboardService::class)->buildLeaderboard($division, 'FINAL', $finalist->division_group_id)
            ->first(fn ($r) => (int) $r['registration']->id === $registrationId);
        if ($row && ($row['run1'] !== null || $row['run2'] !== null)) {
            return 'Finalis ini sudah punya nilai final, tidak bisa dipindah heat.';
        }

        $finalist->update(['division_group_id' => $target->id]);
        LiveScoreUpdated::push($division->event_id);

        return null;
    }

    public function reopenQualification(EventDivision $division): void
    {
        $division->update(['live_stage' => 'QUALIFICATION', 'best_trick_active' => false]);
    }

    public function setBestTrick(EventDivision $division, bool $active): void
    {
        $division->update(['best_trick_active' => $active]);
    }

    // ─── Penyelesaian, buka ulang, reset ────────────────────────────────────────

    /** Hitung ranking final & tambahkan poin ke rider. */
    public function completeFinal(EventDivision $division): void
    {
        if ($division->live_final_completed_at) return;
        app(RankingService::class)->calculateForLiveFinal($division);
    }

    /** Batalkan poin/wins/podium dari final yang sudah selesai lalu kembali ke kualifikasi (poin tidak dobel kalau diselesaikan lagi). */
    public function reopenCompletedFinal(EventDivision $division): void
    {
        if (!$division->live_final_completed_at) return;
        app(RankingService::class)->revertLiveFinal($division);
        $division->update(['live_stage' => 'QUALIFICATION']);
    }

    /**
     * Hapus semua nilai juri di divisi ini (kedua tahap), finalis, heat final, pengumuman, dan Best Trick;
     * divisi kembali ke kualifikasi. Poin final yang sudah dihitung dibatalkan dulu.
     */
    public function resetScores(EventDivision $division): void
    {
        app(RankingService::class)->revertLiveFinal($division);

        DB::transaction(function () use ($division) {
            JudgeScore::where('division_id', $division->id)->delete();
            DivisionFinalist::where('event_division_id', $division->id)->delete();
            DivisionGroup::where('event_division_id', $division->id)->where('stage', 'FINAL')->delete();

            $division->update([
                'live_stage'                 => 'QUALIFICATION',
                'live_final_completed_at'    => null,
                'best_trick_active'          => false,
                'qualification_announced_at' => null,
                'final_announced_at'         => null,
            ]);
        });

        // delete() massal tidak memicu observer, jadi layar /live diberi tahu manual supaya leaderboard langsung kosong.
        LiveScoreUpdated::push($division->event_id);
    }

    // ─── Group kualifikasi ──────────────────────────────────────────────────────

    public function qualificationGroups(EventDivision $division): Collection
    {
        return DivisionGroup::where('event_division_id', $division->id)->where('stage', 'QUALIFICATION')
            ->withCount('registrations')->orderBy('name')->get();
    }

    public function createGroup(EventDivision $division, string $name): DivisionGroup
    {
        return DivisionGroup::create([
            'event_division_id' => $division->id,
            'name'              => $name,
            'stage'             => 'QUALIFICATION',
        ]);
    }

    public function deleteGroup(DivisionGroup $group): void
    {
        Registration::where('division_group_id', $group->id)->update(['division_group_id' => null]);
        $group->delete();
        LiveScoreUpdated::push($group->division->event_id);
    }

    public function assignToGroup(Registration $registration, ?DivisionGroup $group): void
    {
        $registration->update(['division_group_id' => $group?->id]);
        LiveScoreUpdated::push($registration->event_id);
    }

    /** Acak semua peserta approved ke group kualifikasi. False kalau belum ada group. */
    public function randomizeGroups(EventDivision $division): bool
    {
        $groups = DivisionGroup::where('event_division_id', $division->id)->where('stage', 'QUALIFICATION')->pluck('id');
        if ($groups->isEmpty()) return false;

        $regIds = Registration::where('division_id', $division->id)->where('status', 'APPROVED')
            ->pluck('id')->shuffle()->values();

        foreach ($regIds as $i => $regId) {
            Registration::where('id', $regId)->update(['division_group_id' => $groups[$i % $groups->count()]]);
        }
        LiveScoreUpdated::push($division->event_id);

        return true;
    }
}
