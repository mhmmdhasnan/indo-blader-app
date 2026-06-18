<?php

namespace App\Livewire\Judge;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\BattleSubmission;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventJudgeAssignment;
use App\Models\JudgeScore;
use App\Models\QualificationMatch;
use App\Models\QualificationRound;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\RiderCategory;
use App\Models\ScoringCriterion;
use App\Models\Trick;
use App\Services\BracketService;
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

    // Global active event (synced to all sub-selectors)
    public int $activeEventId = 0;

    // Scoring — dynamic criteria arrays
    public array  $criteriaScores  = [];   // rider A (live + knockout)
    public array  $criteriaScoresB = [];   // rider B (knockout only)
    public bool   $scoreSubmitted  = false;
    public int    $judgeEventId    = 0;
    public string $scoringMode     = 'live';   // live | knockout
    public string $koMatchType     = 'QUALIFICATION';
    public int    $koMatchId       = 0;
    public int    $liveRiderId     = 0;
    public int    $liveRunNumber   = 1;

    // Category review
    public int    $moveToCategoryId = 0;
    public string $categoryNotes    = '';

    // Qualification
    public int $selectedEventId     = 0;
    public int $selectedQualRoundId = 0;
    public int $qualTrickId         = 0;
    public int $manualRiderAId      = 0;
    public int $manualRiderBId      = 0;

    // Qualification editing
    public int    $editQualRoundId   = 0;
    public string $editQualRoundName = '';
    public string $editQualPairing   = 'MANUAL';

    // Submission review
    public string $submissionFeedback = '';

    // ─── Criteria init ────────────────────────────────────────────────────────

    public function mount(): void
    {
        $active = Event::where('status', 'LIVE')->orderBy('date')->first()
            ?? Event::orderByRaw("ABS(DATEDIFF(date, NOW()))")->orderBy('date')->first()
            ?? Event::orderBy('date')->first();

        if ($active) {
            $this->activeEventId   = $active->id;
            $this->judgeEventId    = $active->id;
            $this->selectedEventId = $active->id;
            $this->initCriteria();
        }
    }

    public function updatedActiveEventId(): void
    {
        $this->judgeEventId    = $this->activeEventId;
        $this->selectedEventId = $this->activeEventId;
        $this->scoreSubmitted  = false;
        $this->koMatchId       = 0;
        $this->liveRiderId     = 0;
        $this->initCriteria();
    }

    public function updatedJudgeEventId(): void
    {
        $this->initCriteria();
        $this->scoreSubmitted = false;
        $this->koMatchId      = 0;
    }

    public function updatedScoringMode(): void
    {
        $this->initCriteria();
        $this->scoreSubmitted = false;
    }

    private function initCriteria(): void
    {
        $mode     = strtoupper($this->scoringMode);  // LIVE | KNOCKOUT
        $criteria = $this->loadCriteria($mode);

        $this->criteriaScores  = [];
        $this->criteriaScoresB = [];

        foreach ($criteria as $c) {
            $this->criteriaScores[$c->key]  = 9.0;
            $this->criteriaScoresB[$c->key] = 9.0;
        }
    }

    private function loadCriteria(string $mode): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->judgeEventId) {
            $event    = Event::find($this->judgeEventId);
            $criteria = $event?->criteriaFor($mode) ?? collect();
            if ($criteria->count()) return $criteria;
        }
        // Fallback to all active global criteria
        return ScoringCriterion::where('is_active', true)->orderBy('display_order')->get();
    }

    // ─── Scoring ─────────────────────────────────────────────────────────────

    public function submitScore(): void
    {
        if (!$this->judgeEventId || !$this->liveRiderId) {
            $this->addError('judgeEventId', 'Pilih event dan rider terlebih dahulu.');
            return;
        }

        $score = JudgeScore::firstOrCreate(
            [
                'judge_user_id' => auth()->id(),
                'event_id'      => $this->judgeEventId,
                'rider_id'      => $this->liveRiderId,
                'run_number'    => $this->liveRunNumber,
                'scoring_mode'  => 'LIVE',
            ],
            ['status' => 'WAITING']
        );

        app(ScoringService::class)->submitScore($score, $this->criteriaScores);

        $this->scoreSubmitted = true;
    }

    public function submitKnockoutScore(): void
    {
        if (!$this->koMatchId) return;

        $svc = app(ScoringService::class);

        if ($this->koMatchType === 'BRACKET') {
            $match  = BracketMatch::findOrFail($this->koMatchId);
            $totalA = $svc->calculateTotal($this->criteriaScores);
            $totalB = $svc->calculateTotal($this->criteriaScoresB);
            $match->update(['score_a' => $totalA, 'score_b' => $totalB]);

            // Save per-judge KO scores so other judges can see them
            if ($match->rider_a_registration_id) {
                $riderIdA = Rider::where('user_id', Registration::find($match->rider_a_registration_id)?->user_id)->value('id');
                if ($riderIdA) {
                    $scoreA = JudgeScore::updateOrCreate(
                        ['event_id' => $this->judgeEventId, 'rider_id' => $riderIdA, 'run_number' => $this->koMatchId, 'judge_user_id' => auth()->id(), 'scoring_mode' => 'KNOCKOUT'],
                        ['total' => $totalA, 'status' => 'DONE']
                    );
                    $svc->submitScore($scoreA, $this->criteriaScores);
                }
            }
            if ($match->rider_b_registration_id) {
                $riderIdB = Rider::where('user_id', Registration::find($match->rider_b_registration_id)?->user_id)->value('id');
                if ($riderIdB) {
                    $scoreB = JudgeScore::updateOrCreate(
                        ['event_id' => $this->judgeEventId, 'rider_id' => $riderIdB, 'run_number' => $this->koMatchId, 'judge_user_id' => auth()->id(), 'scoring_mode' => 'KNOCKOUT'],
                        ['total' => $totalB, 'status' => 'DONE']
                    );
                    $svc->submitScore($scoreB, $this->criteriaScoresB);
                }
            }
        }

        $this->scoreSubmitted = true;
    }

    public function resetScore(): void
    {
        $this->scoreSubmitted  = false;
        $this->initCriteria();
    }

    // ─── Reset winner ─────────────────────────────────────────────────────────

    public function resetQualMatchWinner(int $matchId): void
    {
        if (!auth()->user()->isHeadJudge()) {
            $this->addError('koMatchId', 'Hanya Head Judge yang dapat membatalkan pemenang.');
            return;
        }
        $match = QualificationMatch::with('qualificationRound')->findOrFail($matchId);
        $match->update(['winner_registration_id' => null, 'status' => 'PENDING']);

        $this->scoreSubmitted = false;
        $this->koMatchId      = $matchId;
        $this->koMatchType    = 'QUALIFICATION';
        $this->view           = 'judging';
        $this->scoringMode    = 'knockout';
        $this->judgeEventId   = $match->qualificationRound->event_id;
        $this->initCriteria();
    }

    public function resetBracketMatchWinner(int $matchId): void
    {
        if (!auth()->user()->isHeadJudge()) {
            $this->addError('koMatchId', 'Hanya Head Judge yang dapat membatalkan pemenang.');
            return;
        }
        $match = BracketMatch::with('bracket')->findOrFail($matchId);
        app(BracketService::class)->resetWinner($match);

        $this->scoreSubmitted = false;
        $this->koMatchId      = $matchId;
        $this->koMatchType    = 'BRACKET';
        $this->view           = 'judging';
        $this->scoringMode    = 'knockout';
        $this->judgeEventId   = $match->bracket->event_id;
        $this->initCriteria();
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

    public function cancelEditQualRound(): void { $this->editQualRoundId = 0; }

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
        if (!auth()->user()->isHeadJudge()) {
            $this->addError('koMatchId', 'Hanya Head Judge yang dapat menetapkan pemenang.');
            return;
        }
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

    // ─── Bracket ─────────────────────────────────────────────────────────────

    public function advanceBracketWinner(int $matchId, int $winnerRegId): void
    {
        if (!auth()->user()->isHeadJudge()) {
            $this->addError('koMatchId', 'Hanya Head Judge yang dapat menetapkan pemenang.');
            return;
        }
        $match  = BracketMatch::with('bracket')->findOrFail($matchId);
        $winner = Registration::findOrFail($winnerRegId);

        $service = app(BracketService::class);
        if ($match->bracket->type === 'DOUBLE_ELIMINATION') {
            $service->advanceWinnerDoubleElim($match, $winner);
        } else {
            $service->advanceWinner($match, $winner);
        }

        $this->scoreSubmitted = false;
        $this->koMatchId      = 0;
    }

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

    public function deleteBracket(int $id): void   { Bracket::findOrFail($id)->delete(); }
    public function deleteBracketMatch(int $id): void { BracketMatch::findOrFail($id)->delete(); }

    // ─── Submission Review ────────────────────────────────────────────────────

    public function approveSubmission(int $id): void
    {
        if (!auth()->user()->isHeadJudge()) {
            $this->addError('submission', 'Hanya Head Judge yang dapat approve submission.');
            return;
        }
        BattleSubmission::findOrFail($id)->update([
            'status'      => 'APPROVED',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejectSubmission(int $id, string $feedback = ''): void
    {
        if (!auth()->user()->isHeadJudge()) {
            $this->addError('submission', 'Hanya Head Judge yang dapat reject submission.');
            return;
        }
        BattleSubmission::findOrFail($id)->update([
            'status'         => 'REJECTED',
            'judge_feedback' => $feedback ?: null,
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
        ]);
    }

    public function requestReupload(int $id, string $feedback = ''): void
    {
        if (!auth()->user()->isHeadJudge()) {
            $this->addError('submission', 'Hanya Head Judge yang dapat meminta re-upload.');
            return;
        }
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
            'events'          => Event::orderBy('date')->get(),
            'activeEvent'     => $this->activeEventId ? Event::find($this->activeEventId) : null,
            'eventCriteria'   => collect(),
            'judgeAssignment' => null,
            'otherJudgeScores'=> collect(),
        ];

        if ($this->view === 'judging') {
            $mode     = strtoupper($this->scoringMode);
            $criteria = $this->loadCriteria($mode);

            // Initialize criteria arrays if empty
            if (empty($this->criteriaScores)) {
                foreach ($criteria as $c) {
                    $this->criteriaScores[$c->key]  = 9.0;
                    $this->criteriaScoresB[$c->key] = 9.0;
                }
            }

            $data['eventCriteria'] = $criteria;

            // Judge assignment for selected event
            $data['judgeAssignment'] = $this->judgeEventId
                ? EventJudgeAssignment::where('event_id', $this->judgeEventId)
                    ->where('user_id', auth()->id())
                    ->first()
                : null;

            $data['judgeRiders'] = $this->judgeEventId
                ? Rider::whereHas('registrations', fn ($q) => $q->where('event_id', $this->judgeEventId))->orderBy('name')->get()
                : Rider::orderBy('name')->get();

            // Other judges' live scores for current rider/run
            if ($this->scoringMode === 'live' && $this->judgeEventId && $this->liveRiderId) {
                $data['otherJudgeScores'] = JudgeScore::where('event_id', $this->judgeEventId)
                    ->where('rider_id', $this->liveRiderId)
                    ->where('run_number', $this->liveRunNumber)
                    ->where('scoring_mode', 'LIVE')
                    ->with(['judge', 'scoreDetails'])
                    ->get();
            } else {
                $data['otherJudgeScores'] = collect();
            }

            // Other judges' KO scores for current match (bracket only)
            $data['koOtherJudgeScoresA'] = collect();
            $data['koOtherJudgeScoresB'] = collect();
            if ($this->scoringMode === 'knockout' && $this->koMatchId && $this->koMatchType === 'BRACKET') {
                $match = BracketMatch::find($this->koMatchId);
                if ($match) {
                    if ($match->rider_a_registration_id) {
                        $riderIdA = Rider::where('user_id', Registration::find($match->rider_a_registration_id)?->user_id)->value('id');
                        if ($riderIdA) {
                            $data['koOtherJudgeScoresA'] = JudgeScore::where('event_id', $this->judgeEventId)
                                ->where('rider_id', $riderIdA)
                                ->where('run_number', $this->koMatchId)
                                ->where('scoring_mode', 'KNOCKOUT')
                                ->with(['judge', 'scoreDetails'])
                                ->get();
                        }
                    }
                    if ($match->rider_b_registration_id) {
                        $riderIdB = Rider::where('user_id', Registration::find($match->rider_b_registration_id)?->user_id)->value('id');
                        if ($riderIdB) {
                            $data['koOtherJudgeScoresB'] = JudgeScore::where('event_id', $this->judgeEventId)
                                ->where('rider_id', $riderIdB)
                                ->where('run_number', $this->koMatchId)
                                ->where('scoring_mode', 'KNOCKOUT')
                                ->with(['judge', 'scoreDetails'])
                                ->get();
                        }
                    }
                }
            }

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
                    ? QualificationMatch::with(['riderA', 'riderB', 'trick', 'winner'])->find($this->koMatchId)
                    : BracketMatch::with(['riderA', 'riderB', 'trick', 'winner'])->find($this->koMatchId))
                : null;

            if ($this->koMatchId) {
                $submissionMatchType = $this->koMatchType === 'BRACKET' ? 'PLAYOFF' : 'QUALIFICATION';
                $data['koApprovedSubmissions'] = BattleSubmission::where('match_type', $submissionMatchType)
                    ->where('match_id', $this->koMatchId)
                    ->where('status', 'APPROVED')
                    ->with('registration')
                    ->get();
            } else {
                $data['koApprovedSubmissions'] = collect();
            }
        }

        $eid = $this->activeEventId ?: null;

        if ($this->view === 'categories') {
            $data['pendingCategoryAssignments'] = RiderCategory::with(['registration.event', 'category'])
                ->where('status', 'PENDING')
                ->whereHas('registration', fn ($q) => $q->where('status', 'APPROVED')
                    ->when($eid, fn ($q) => $q->where('event_id', $eid)))
                ->latest()->get();
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
                ->when($eid, fn ($q) => $q->where('event_id', $eid))
                ->orderBy('round_number')->get();
            $data['tricks']                = Trick::where('is_active', true)->orderBy('name')->get();
            $data['approvedRegistrations'] = $eid
                ? Registration::where('event_id', $eid)->where('status', 'APPROVED')->orderBy('name')->get()
                : collect();
        }

        if ($this->view === 'submissions') {
            $data['pendingSubmissions'] = BattleSubmission::with('registration')
                ->where('status', 'PENDING')
                ->when($eid, fn ($q) => $q->whereHas('registration', fn ($q) => $q->where('event_id', $eid)))
                ->latest()->get();
        }

        if ($this->view === 'brackets') {
            $data['brackets'] = Bracket::with(['event', 'bracketMatches.riderA', 'bracketMatches.riderB', 'bracketMatches.winner', 'bracketMatches.trick'])
                ->when($eid, fn ($q) => $q->where('event_id', $eid))
                ->latest()->get();
            $data['tricks'] = Trick::where('is_active', true)->orderBy('name')->get();
        }

        return view('livewire.judge.dashboard', $data);
    }
}
