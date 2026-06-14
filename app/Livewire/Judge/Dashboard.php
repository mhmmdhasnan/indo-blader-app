<?php

namespace App\Livewire\Judge;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\BattleSubmission;
use App\Models\Category;
use App\Models\Event;
use App\Models\JudgeScore;
use App\Models\QualificationMatch;
use App\Models\QualificationRound;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\RiderCategory;
use App\Models\Trick;
use App\Services\NotificationService;
use App\Services\QualificationService;
use App\Services\ScoringService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Judge Panel — Indo Blader')]
class Dashboard extends Component
{
    public string $view = 'judging';

    // Judging
    public float  $judgeExec         = 9.2;
    public float  $judgeStyle        = 8.9;
    public float  $judgeCreativity   = 9.4;
    public float  $judgeDiff         = 9.5;
    public float  $judgeConsistency  = 9.0;
    public float  $judgeExecB        = 9.2;
    public float  $judgeStyleB       = 8.9;
    public float  $judgeCreativityB  = 9.4;
    public float  $judgeDiffB        = 9.5;
    public float  $judgeConsistencyB = 9.0;
    public bool   $scoreSubmitted    = false;
    public int    $judgeEventId     = 0;
    public string $scoringMode      = 'live';   // live | knockout
    public string $koMatchType      = 'QUALIFICATION';
    public int    $koMatchId        = 0;
    public int    $liveRiderId      = 0;
    public int    $liveRunNumber    = 1;

    // Category review
    public int    $moveToCategoryId = 0;
    public string $categoryNotes    = '';

    // Qualification
    public int $selectedEventId     = 0;
    public int $selectedQualRoundId = 0;
    public int $qualTrickId         = 0;
    public int $manualRiderAId      = 0;
    public int $manualRiderBId      = 0;

    // Bracket — trick selected per match via Alpine, not a shared Livewire property

    // Qualification editing
    public int    $editQualRoundId   = 0;
    public string $editQualRoundName = '';
    public string $editQualPairing   = 'MANUAL';

    // Submission review
    public string $submissionFeedback = '';

    // ─── Scoring ─────────────────────────────────────────────────────────────

    public function submitScore(): void
    {
        if (!$this->judgeEventId || !$this->liveRiderId) {
            $this->addError('judgeEventId', 'Pilih event dan rider terlebih dahulu.');
            return;
        }

        $score = JudgeScore::firstOrCreate(
            [
                'event_id'   => $this->judgeEventId,
                'rider_id'   => $this->liveRiderId,
                'run_number' => $this->liveRunNumber,
            ],
            ['status' => 'WAITING']
        );

        app(ScoringService::class)->submitScore($score, [
            'execution'   => $this->judgeExec,
            'style'       => $this->judgeStyle,
            'creativity'  => $this->judgeCreativity,
            'difficulty'  => $this->judgeDiff,
            'consistency' => $this->judgeConsistency,
        ]);

        $this->scoreSubmitted = true;
    }

    public function submitKnockoutScore(): void
    {
        if (!$this->koMatchId) return;

        if ($this->koMatchType === 'BRACKET') {
            $match  = BracketMatch::findOrFail($this->koMatchId);
            $totalA = round(($this->judgeExec + $this->judgeStyle + $this->judgeCreativity + $this->judgeDiff + $this->judgeConsistency) / 5 * 10, 1);
            $totalB = round(($this->judgeExecB + $this->judgeStyleB + $this->judgeCreativityB + $this->judgeDiffB + $this->judgeConsistencyB) / 5 * 10, 1);
            $match->update(['score_a' => $totalA, 'score_b' => $totalB]);
        }

        $this->scoreSubmitted = true;
    }

    public function resetScore(): void
    {
        $this->scoreSubmitted    = false;
        $this->judgeExec         = 9.2;
        $this->judgeStyle        = 8.9;
        $this->judgeCreativity   = 9.4;
        $this->judgeDiff         = 9.5;
        $this->judgeConsistency  = 9.0;
        $this->judgeExecB        = 9.2;
        $this->judgeStyleB       = 8.9;
        $this->judgeCreativityB  = 9.4;
        $this->judgeDiffB        = 9.5;
        $this->judgeConsistencyB = 9.0;
    }

    // ─── Category Management ──────────────────────────────────────────────────

    public function approveCategoryAssignment(int $ridCatId, string $notes = ''): void
    {
        $ridCat = RiderCategory::with(['registration', 'category'])->findOrFail($ridCatId);
        $ridCat->update(['status' => 'APPROVED', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        $ridCat->reviewLogs()->create([
            'action'         => 'APPROVED',
            'to_category_id' => $ridCat->category_id,
            'performed_by'   => auth()->id(),
            'notes'          => $notes ?: null,
        ]);
        NotificationService::send($ridCat->registration, 'registration_approved',
            'Category Approved',
            "Your category assignment ({$ridCat->category->name}) has been approved.");
    }

    public function rejectCategoryAssignment(int $ridCatId, string $notes = ''): void
    {
        $ridCat = RiderCategory::with(['registration', 'category'])->findOrFail($ridCatId);
        $ridCat->update([
            'status'      => 'REJECTED',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'notes'       => $notes ?: null,
        ]);
        $ridCat->reviewLogs()->create([
            'action'           => 'REJECTED',
            'from_category_id' => $ridCat->category_id,
            'performed_by'     => auth()->id(),
            'notes'            => $notes ?: null,
        ]);
    }

    public function moveCategoryAssignment(int $ridCatId, int $catId, string $notes = ''): void
    {
        if (!$catId) return;
        $ridCat   = RiderCategory::with(['registration', 'category'])->findOrFail($ridCatId);
        $newCat   = Category::findOrFail($catId);
        $oldCatId = $ridCat->category_id;

        $ridCat->update([
            'category_id' => $newCat->id,
            'status'      => 'MOVED',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'notes'       => $notes ?: null,
        ]);
        $ridCat->reviewLogs()->create([
            'action'           => 'MOVED',
            'from_category_id' => $oldCatId,
            'to_category_id'   => $newCat->id,
            'performed_by'     => auth()->id(),
            'notes'            => $notes ?: null,
        ]);
        NotificationService::send($ridCat->registration, 'category_changed',
            'Category Updated',
            "Your competition category has been changed to {$newCat->name}.");
    }

    // ─── Qualification ────────────────────────────────────────────────────────

    public function addManualPairing(int $roundId, int $riderA, int $riderB): void
    {
        if (!$riderA || !$riderB) return;
        if ($riderA === $riderB) {
            $this->addError('manualRiderAId', 'Rider A dan B tidak boleh sama.');
            return;
        }

        QualificationMatch::create([
            'qualification_round_id'  => $roundId,
            'rider_a_registration_id' => $riderA,
            'rider_b_registration_id' => $riderB,
        ]);
    }

    public function editQualRound(int $id): void
    {
        $round = QualificationRound::findOrFail($id);
        $this->editQualRoundId   = $id;
        $this->editQualRoundName = $round->name;
        $this->editQualPairing   = $round->pairing_type;
    }

    public function updateQualRound(): void
    {
        $this->validate(['editQualRoundName' => 'required|string|max:100']);
        QualificationRound::findOrFail($this->editQualRoundId)->update([
            'name'         => $this->editQualRoundName,
            'pairing_type' => $this->editQualPairing,
        ]);
        $this->editQualRoundId = 0;
    }

    public function cancelEditQualRound(): void
    {
        $this->editQualRoundId = 0;
    }

    public function deleteQualRound(int $id): void
    {
        QualificationRound::findOrFail($id)->delete();
    }

    public function deleteQualMatch(int $id): void
    {
        QualificationMatch::findOrFail($id)->delete();
    }

    public function setQualMatchWinner(int $matchId, int $winnerRegId): void
    {
        $match  = QualificationMatch::findOrFail($matchId);
        $winner = Registration::findOrFail($winnerRegId);
        app(QualificationService::class)->setWinner($match, $winner);
    }

    public function assignTrickToQualMatch(int $matchId): void
    {
        if (!$this->qualTrickId) return;
        $match = QualificationMatch::findOrFail($matchId);
        $match->update(['trick_id' => $this->qualTrickId]);

        foreach ([$match->riderA, $match->riderB] as $rider) {
            if ($rider) {
                NotificationService::send($rider, 'trick_assigned', 'Trick Assigned',
                    "A required trick has been assigned to your qualification match.");
            }
        }
        $this->qualTrickId = 0;
    }

    public function advanceBracketWinner(int $matchId, int $winnerRegId): void
    {
        $match  = BracketMatch::with('bracket')->findOrFail($matchId);
        $winner = Registration::findOrFail($winnerRegId);

        $service = app(\App\Services\BracketService::class);

        if ($match->bracket->type === 'DOUBLE_ELIMINATION') {
            $service->advanceWinnerDoubleElim($match, $winner);
        } else {
            $service->advanceWinner($match, $winner);
        }

        $this->scoreSubmitted = false;
        $this->koMatchId      = 0;
    }

    public function deleteBracket(int $id): void
    {
        Bracket::findOrFail($id)->delete();
    }

    public function deleteBracketMatch(int $id): void
    {
        BracketMatch::findOrFail($id)->delete();
    }

    // ─── Bracket ─────────────────────────────────────────────────────────────

    public function assignTrickToBracketMatch(int $matchId, int $trickId): void
    {
        if (!$trickId) return;
        $match = BracketMatch::findOrFail($matchId);
        $match->update(['trick_id' => $trickId]);

        foreach ([$match->rider_a_registration_id, $match->rider_b_registration_id] as $regId) {
            if ($regId) {
                $reg = Registration::find($regId);
                if ($reg) {
                    NotificationService::send($reg, 'trick_assigned', 'Trick Assigned',
                        "A required trick has been assigned to your bracket match.");
                }
            }
        }
    }

    // ─── Submission Review ────────────────────────────────────────────────────

    public function approveSubmission(int $id): void
    {
        BattleSubmission::findOrFail($id)->update([
            'status'      => 'APPROVED',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejectSubmission(int $id, string $feedback = ''): void
    {
        BattleSubmission::findOrFail($id)->update([
            'status'         => 'REJECTED',
            'judge_feedback' => $feedback ?: null,
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
        ]);
    }

    public function requestReupload(int $id, string $feedback = ''): void
    {
        BattleSubmission::findOrFail($id)->update([
            'status'         => 'NEED_REUPLOAD',
            'judge_feedback' => $feedback ?: null,
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
        ]);
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $data = [
            'events' => Event::orderBy('date')->get(),
        ];

        if ($this->view === 'judging') {
            $data['judgeRiders'] = $this->judgeEventId
                ? Rider::whereHas('registrations', fn ($q) => $q->where('event_id', $this->judgeEventId))->orderBy('name')->get()
                : Rider::orderBy('name')->get();
            if ($this->scoringMode === 'knockout' && $this->judgeEventId) {
                if ($this->koMatchType === 'QUALIFICATION') {
                    $data['koMatches'] = QualificationMatch::whereHas('qualificationRound', fn ($q) => $q->where('event_id', $this->judgeEventId))
                        ->with(['riderA', 'riderB', 'qualificationRound'])
                        ->where('status', 'PENDING')
                        ->get();
                } else {
                    $data['koMatches'] = BracketMatch::whereHas('bracket', fn ($q) => $q->where('event_id', $this->judgeEventId))
                        ->with(['riderA', 'riderB', 'bracket'])
                        ->where('status', 'PENDING')
                        ->get();
                }
            }
            $data['koCurrentMatch'] = $this->koMatchId
                ? ($this->koMatchType === 'QUALIFICATION'
                    ? QualificationMatch::with(['riderA', 'riderB', 'trick'])->find($this->koMatchId)
                    : BracketMatch::with(['riderA', 'riderB', 'trick'])->find($this->koMatchId))
                : null;
        }

        if ($this->view === 'categories') {
            $data['pendingCategoryAssignments'] = RiderCategory::with(['registration.event', 'category'])
                ->where('status', 'PENDING')
                ->latest()
                ->get();
            $data['allCategories'] = Category::all();
        }

        if ($this->view === 'qualification') {
            $data['qualificationRounds'] = QualificationRound::with([
                'event',
                'qualificationMatches.riderA',
                'qualificationMatches.riderB',
                'qualificationMatches.trick',
                'qualificationMatches.winner',
            ])
                ->when($this->selectedEventId, fn ($q) => $q->where('event_id', $this->selectedEventId))
                ->orderBy('round_number')
                ->get();
            $data['tricks'] = Trick::where('is_active', true)->orderBy('name')->get();
            $data['approvedRegistrations'] = $this->selectedEventId
                ? Registration::where('event_id', $this->selectedEventId)->where('status', 'APPROVED')->orderBy('name')->get()
                : collect();
        }

        if ($this->view === 'submissions') {
            $data['pendingSubmissions'] = BattleSubmission::with('registration')
                ->where('status', 'PENDING')
                ->latest()
                ->get();
        }

        if ($this->view === 'brackets') {
            $data['brackets'] = Bracket::with(['event', 'bracketMatches.riderA', 'bracketMatches.riderB', 'bracketMatches.winner', 'bracketMatches.trick'])
                ->latest()
                ->get();
            $data['tricks'] = Trick::where('is_active', true)->orderBy('name')->get();
        }

        return view('livewire.judge.dashboard', $data);
    }
}
