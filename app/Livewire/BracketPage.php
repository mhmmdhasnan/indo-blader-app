<?php

namespace App\Livewire;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\JudgeScore;
use App\Services\LiveScoreboardService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bracket — FRAMEBLADESCORE')]
class BracketPage extends Component
{
    public string $selectedSlug       = '';
    public int    $selectedDivisionId = 0;

    public function mount(string $slug = null): void
    {
        if ($slug) {
            $this->selectedSlug = $slug;
        } else {
            $event = $this->resultEvents()->first()
                ?? Event::orderByDesc('date')->first();
            $this->selectedSlug = $event?->slug ?? '';
        }

        $this->initDivision();
    }

    public function updatedSelectedSlug(): void
    {
        $this->initDivision();
    }

    private function initDivision(): void
    {
        $event = $this->selectedSlug ? Event::where('slug', $this->selectedSlug)->first() : null;
        $this->selectedDivisionId = $this->resultDivisions($event)->first()?->id ?? 0;
    }

    /** Events that have something to show here: a bracket, or a live-score division whose final is completed. */
    private function resultEvents(): Collection
    {
        return Event::where(fn ($q) => $q->has('brackets')
                ->orWhereHas('divisions', fn ($d) => $d->whereNotNull('live_final_completed_at')))
            ->orderByDesc('date')
            ->get();
    }

    /**
     * Division tabs: divisions with a bracket (knockout) plus live-score divisions whose
     * final is completed ("Ranking selesai") and that actually have final scores.
     * (The Operator's "Umumkan" button only drives the on-stage /live screen, not this page.)
     */
    private function resultDivisions(?Event $event): Collection
    {
        if (!$event) return collect();

        $bracketDivisions = EventDivision::whereHas('brackets', fn ($q) => $q->where('event_id', $event->id))
            ->where('event_id', $event->id)
            ->get();

        $scoredDivisionIds = JudgeScore::where('event_id', $event->id)
            ->where('scoring_mode', 'LIVE')
            ->where('live_stage', 'FINAL')
            ->where('status', 'DONE')
            ->whereNotNull('division_id')
            ->distinct()
            ->pluck('division_id');

        $liveDivisions = EventDivision::where('event_id', $event->id)
            ->whereNotNull('live_final_completed_at')
            ->whereIn('id', $scoredDivisionIds)
            ->whereNotIn('id', $bracketDivisions->pluck('id'))
            ->get();

        return $bracketDivisions->concat($liveDivisions)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * Adds a competition rank ("1224": equal totals share a rank, same as
     * RankingService::deriveLiveFinalPlacements). Riders without a score get rank null.
     */
    private function withRanks(Collection $rows): Collection
    {
        $rank = 0;
        $prev = null;

        return $rows->values()->map(function ($row, $i) use (&$rank, &$prev) {
            if (($row['total'] ?? 0) > 0) {
                if ($prev === null || $row['total'] < $prev) {
                    $rank = $i + 1;
                }
                $prev = $row['total'];
                $row['rank'] = $rank;
            } else {
                $row['rank'] = null;
            }

            return $row;
        });
    }

    public function render()
    {
        $events = $this->resultEvents();
        $event  = $this->selectedSlug ? Event::where('slug', $this->selectedSlug)->first() : null;

        // Bracket divisions + announced live-score divisions
        $divisions = $this->resultDivisions($event);

        if ($this->selectedDivisionId === 0 && $divisions->count()) {
            $this->selectedDivisionId = $divisions->first()->id;
        }

        $bracket = null;
        if ($event && $this->selectedDivisionId) {
            $bracket = Bracket::where('event_id', $event->id)
                ->where('division_id', $this->selectedDivisionId)
                ->first();
        } elseif ($event) {
            $bracket = Bracket::where('event_id', $event->id)->first();
        }

        // Live-score division (no bracket): final leaderboard, same numbers as /live.
        $liveDivision = (!$bracket && $this->selectedDivisionId)
            ? $divisions->firstWhere('id', $this->selectedDivisionId)
            : null;
        $liveRows = $liveDivision
            ? $this->withRanks(app(LiveScoreboardService::class)->buildLeaderboard($liveDivision, 'FINAL'))
            : collect();

        $matchesByRound = collect();
        if ($bracket) {
            $matchesByRound = BracketMatch::with(['riderA', 'riderB', 'winner', 'trick'])
                ->where('bracket_id', $bracket->id)
                ->orderBy('match_number')
                ->get()
                ->groupBy('round');
        }

        $roundOrder = ['PRELIM', 'QF', 'SF', 'F', 'UB_R1', 'UB_R2', 'UB_SF', 'UB_F', 'LB_R1', 'LB_R2', 'LB_R3', 'LB_R4', 'LB_SF', 'LB_F', 'GF'];

        return view('livewire.bracket-page', compact('events', 'event', 'divisions', 'bracket', 'matchesByRound', 'roundOrder', 'liveDivision', 'liveRows'));
    }
}
