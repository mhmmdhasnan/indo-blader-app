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
#[Title('Judge Panel — FRAMEBLADESCORE')]
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
    public int    $judgeDivisionId = 0;
    public int    $judgeGroupId    = 0;
    public ?float $bestTrickScore  = null; // typed 0-20 score for the Best Trick phase

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
        $active = $this->resolveActiveEvent();

        if ($active) {
            $this->activeEventId   = $active->id;
            $this->judgeEventId    = $active->id;
            $this->selectedEventId = $active->id;
            $this->judgeDivisionId = $active->active_division_id ?? 0;
            $this->judgeGroupId    = $active->active_group_id ?? 0;
            $this->initCriteria();
        }
    }

    private function resolveActiveEvent(): ?Event
    {
        $settingId = \App\Models\Setting::get('active_event_id');
        if ($settingId && $event = Event::find($settingId)) {
            return $event;
        }

        return Event::where('status', 'LIVE')->orderBy('date')->first()
            ?? Event::orderByRaw("ABS(DATEDIFF(date, NOW()))")->orderBy('date')->first()
            ?? Event::orderBy('date')->first();
    }

    public function updatedActiveEventId(): void
    {
        if (auth()->user()->isOperator()) {
            \App\Models\Setting::set('active_event_id', $this->activeEventId ?: null);
        }

        $this->judgeEventId    = $this->activeEventId;
        $this->selectedEventId = $this->activeEventId;
        $this->scoreSubmitted  = false;
        $this->koMatchId       = 0;
        $this->liveRiderId     = 0;
        $this->initCriteria();
    }

    public function updatedJudgeEventId(): void
    {
        $event = Event::find($this->judgeEventId);
        $this->judgeDivisionId = $event?->active_division_id ?? 0;
        $this->judgeGroupId    = $event?->active_group_id ?? 0;
        $this->initCriteria();
        $this->scoreSubmitted = false;
        $this->koMatchId      = 0;
    }

    public function updatedJudgeDivisionId(): void
    {
        $this->judgeGroupId = 0;

        if ($this->judgeEventId && $this->canControlLiveSession()) {
            Event::whereKey($this->judgeEventId)->update([
                'active_division_id' => $this->judgeDivisionId ?: null,
                'active_group_id'    => null,
            ]);
        }
    }

    public function updatedJudgeGroupId(): void
    {
        if ($this->judgeEventId && $this->canControlLiveSession()) {
            Event::whereKey($this->judgeEventId)->update([
                'active_group_id' => $this->judgeGroupId ?: null,
            ]);
        }
    }

    private function canControlLiveSession(): bool
    {
        return auth()->user()->isHeadJudge() || auth()->user()->isOperator();
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
            $this->criteriaScores[$c->key]  = 90.0;
            $this->criteriaScoresB[$c->key] = 90.0;
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
        if (auth()->user()->isOperator()) return;

        if (!$this->judgeEventId || !$this->liveRiderId) {
            $this->addError('judgeEventId', 'Pilih event dan rider terlebih dahulu.');
            return;
        }

        $riderId = $this->resolveRiderIdFromRegistration($this->liveRiderId);
        if (!$riderId) {
            $this->addError('liveRiderId', 'Rider tidak ditemukan untuk peserta ini.');
            return;
        }

        $stage = Registration::find($this->liveRiderId)?->division?->live_stage ?? 'QUALIFICATION';

        $score = JudgeScore::firstOrCreate(
            [
                'judge_user_id' => auth()->id(),
                'event_id'      => $this->judgeEventId,
                'rider_id'      => $riderId,
                'run_number'    => $this->liveRunNumber,
                'scoring_mode'  => 'LIVE',
                'live_stage'    => $stage,
            ],
            ['status' => 'WAITING']
        );

        app(ScoringService::class)->submitScore($score, $this->criteriaScores);

        $this->scoreSubmitted = true;
    }

    public function submitBestTrickScore(): void
    {
        if (auth()->user()->isOperator()) return;

        if (!$this->judgeEventId || !$this->liveRiderId) {
            $this->addError('judgeEventId', 'Pilih event dan rider terlebih dahulu.');
            return;
        }

        $this->validate([
            'bestTrickScore' => 'required|numeric|min:0|max:20',
        ], [], ['bestTrickScore' => 'skor best trick']);

        $riderId = $this->resolveRiderIdFromRegistration($this->liveRiderId);
        if (!$riderId) {
            $this->addError('liveRiderId', 'Rider tidak ditemukan untuk peserta ini.');
            return;
        }

        JudgeScore::updateOrCreate(
            [
                'judge_user_id' => auth()->id(),
                'event_id'      => $this->judgeEventId,
                'rider_id'      => $riderId,
                'run_number'    => $this->liveRunNumber,
                'scoring_mode'  => 'BEST_TRICK',
                'live_stage'    => 'FINAL',
            ],
            ['total' => $this->bestTrickScore, 'status' => 'DONE']
        );

        $this->scoreSubmitted  = true;
        $this->bestTrickScore  = null;
    }

    private function resolveRiderIdFromRegistration(int $registrationId): ?int
    {
        $reg = Registration::find($registrationId);
        if (!$reg) return null;

        // Try to find existing Rider by user_id
        if ($reg->user_id) {
            $rider = Rider::where('user_id', $reg->user_id)->first();
            if ($rider) return $rider->id;
        }

        // Try by name
        $rider = Rider::where('name', $reg->name)->first();
        if ($rider) return $rider->id;

        // Auto-create Rider record for this registrant
        $age = $reg->dob ? (int) $reg->dob->diffInYears(now()) : 0;
        $rider = Rider::create([
            'user_id'  => $reg->user_id,
            'name'     => $reg->name,
            'nick'     => $reg->name,
            'city'     => $reg->city ?? '-',
            'age'      => $age,
            'category' => in_array($reg->category, ['STREET','PARK','VERT','FLAT','MINIRAMP']) ? $reg->category : 'STREET',
            'stance'   => in_array($reg->stance, ['Regular','Goofy']) ? $reg->stance : 'Regular',
            'slug'     => \Illuminate\Support\Str::slug($reg->name . '-' . $reg->id),
        ]);

        return $rider->id;
    }

    private function currentLiveStage(Event $event): string
    {
        if (!$event->live_rider_id) return 'QUALIFICATION';

        $liveRider = Rider::find($event->live_rider_id);
        if (!$liveRider) return 'QUALIFICATION';

        $reg = Registration::where('event_id', $event->id)
            ->where('status', 'APPROVED')
            ->where(function ($q) use ($liveRider) {
                if ($liveRider->user_id) {
                    $q->where('user_id', $liveRider->user_id)
                      ->orWhere('name', $liveRider->name);
                } else {
                    $q->where('name', $liveRider->name);
                }
            })
            ->first();

        return $reg?->division?->live_stage ?? 'QUALIFICATION';
    }

    private function currentIsBestTrick(Event $event): bool
    {
        if (!$event->live_rider_id) return false;

        $liveRider = Rider::find($event->live_rider_id);
        if (!$liveRider) return false;

        $reg = Registration::where('event_id', $event->id)
            ->where('status', 'APPROVED')
            ->where(function ($q) use ($liveRider) {
                if ($liveRider->user_id) {
                    $q->where('user_id', $liveRider->user_id)
                      ->orWhere('name', $liveRider->name);
                } else {
                    $q->where('name', $liveRider->name);
                }
            })
            ->first();

        return $reg?->division?->best_trick_active ?? false;
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

    // ─── Live Session Sync (everyone except Operator, who sets the state) ─────

    public function syncLiveState(): void
    {
        if (auth()->user()->isOperator()) return;

        $settingEventId = \App\Models\Setting::get('active_event_id');
        if ($settingEventId && $this->activeEventId !== (int) $settingEventId) {
            $newEvent = Event::find($settingEventId);
            if ($newEvent) {
                $this->activeEventId   = $newEvent->id;
                $this->judgeEventId    = $newEvent->id;
                $this->selectedEventId = $newEvent->id;
                $this->judgeDivisionId = $newEvent->active_division_id ?? 0;
                $this->judgeGroupId    = $newEvent->active_group_id ?? 0;
                $this->scoreSubmitted  = false;
                $this->koMatchId       = 0;
                $this->liveRiderId     = 0;
                $this->initCriteria();
            }
        }

        $event = $this->judgeEventId ? Event::find($this->judgeEventId) : null;
        if (!$event || !in_array($event->live_phase, ['NEXT', 'RUNNING'], true)) {
            return;
        }

        $liveRider = $event->live_rider_id ? Rider::find($event->live_rider_id) : null;
        if (!$liveRider) return;

        $reg = Registration::where('event_id', $this->judgeEventId)
            ->where('status', 'APPROVED')
            ->where(function ($q) use ($liveRider) {
                if ($liveRider->user_id) {
                    $q->where('user_id', $liveRider->user_id)
                      ->orWhere('name', $liveRider->name);
                } else {
                    $q->where('name', $liveRider->name);
                }
            })
            ->first();

        if (!$reg) return;

        $eventRunNumber = $event->live_run_number ?? 1;
        $riderChanged   = $this->liveRiderId !== $reg->id;
        $runChanged     = $this->liveRunNumber !== $eventRunNumber;

        if ($riderChanged || $runChanged) {
            $this->liveRiderId   = $reg->id;
            $this->liveRunNumber = $eventRunNumber;
            $this->bestTrickScore = null;
            $this->initCriteria();
        }

        // Selalu cek ulang ke database — bukan cuma saat rider/run berganti —
        // supaya kalau run ini sempat dibatalkan (skor dihapus) lalu dijalankan
        // ulang dengan rider & run number yang sama, tombol Submit Score muncul
        // lagi alih-alih nyangkut di status "sudah submit" yang basi.
        if ($event->live_phase === 'RUNNING') {
            $isBestTrick = $this->currentIsBestTrick($event);
            $wasSubmitted = $this->scoreSubmitted;
            $this->scoreSubmitted = JudgeScore::where('event_id', $event->id)
                ->where('rider_id', $event->live_rider_id)
                ->where('run_number', $eventRunNumber)
                ->where('scoring_mode', $isBestTrick ? 'BEST_TRICK' : 'LIVE')
                ->where('live_stage', $this->currentLiveStage($event))
                ->where('judge_user_id', auth()->id())
                ->where('status', 'DONE')
                ->exists();

            if ($wasSubmitted && !$this->scoreSubmitted) {
                $this->initCriteria();
            }
        }
    }

    // ─── Live Session Control (Head Judge starts/reveals, Operator sets up) ───

    public function showNextRider(): void
    {
        if (!$this->canControlLiveSession()) return;
        if (!$this->judgeEventId || !$this->liveRiderId) return;

        $riderId = $this->resolveRiderIdFromRegistration($this->liveRiderId);
        if (!$riderId) return;

        Event::findOrFail($this->judgeEventId)->update([
            'live_rider_id'   => $riderId,
            'live_run_number' => $this->liveRunNumber,
            'live_phase'      => 'NEXT',
        ]);
    }

    public function startRun(): void
    {
        if (!auth()->user()->isHeadJudge()) return;
        if (!$this->judgeEventId || !$this->liveRiderId) {
            $this->addError('liveRiderId', 'Pilih event dan rider terlebih dahulu.');
            return;
        }
        $riderId = $this->resolveRiderIdFromRegistration($this->liveRiderId);
        if (!$riderId) {
            $this->addError('liveRiderId', 'Rider tidak ditemukan.');
            return;
        }
        Event::findOrFail($this->judgeEventId)->update([
            'live_rider_id'   => $riderId,
            'live_run_number' => $this->liveRunNumber,
            'live_phase'      => 'RUNNING',
            'live_started_at' => now(),
        ]);

        $this->scoreSubmitted = false;
        $this->bestTrickScore = null;
        $this->initCriteria();
    }

    public function revealScore(): void
    {
        if (!auth()->user()->isHeadJudge()) return;
        Event::findOrFail($this->judgeEventId)->update(['live_phase' => 'REVEALING']);
    }

    public function endSession(): void
    {
        if (!$this->canControlLiveSession()) return;

        $event = Event::findOrFail($this->judgeEventId);

        // Membatalkan run yang sedang berjalan (bukan sekadar batal preview atau
        // kembali dari layar reveal) juga menghapus skor yang sudah disubmit
        // untuk run itu, supaya tidak ada skor "nyangkut" kalau rider run ulang.
        if ($event->live_phase === 'RUNNING' && $event->live_rider_id && $event->live_run_number) {
            JudgeScore::where('event_id', $event->id)
                ->where('rider_id', $event->live_rider_id)
                ->where('run_number', $event->live_run_number)
                ->where('scoring_mode', $this->currentIsBestTrick($event) ? 'BEST_TRICK' : 'LIVE')
                ->where('live_stage', $this->currentLiveStage($event))
                ->delete();
        }

        $event->update([
            'live_rider_id'   => null,
            'live_run_number' => null,
            'live_phase'      => null,
            'live_started_at' => null,
        ]);
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
                    $this->criteriaScores[$c->key]  = 90.0;
                    $this->criteriaScoresB[$c->key] = 90.0;
                }
            }

            $data['eventCriteria'] = $criteria;

            // Judge assignment for selected event
            $data['judgeAssignment'] = $this->judgeEventId
                ? EventJudgeAssignment::where('event_id', $this->judgeEventId)
                    ->where('user_id', auth()->id())
                    ->first()
                : null;

            $data['judgeDivisions'] = $this->judgeEventId
                ? \App\Models\EventDivision::where('event_id', $this->judgeEventId)->where('is_active', true)->orderBy('name')->get()
                : collect();

            $selectedDivision = $this->judgeDivisionId ? \App\Models\EventDivision::find($this->judgeDivisionId) : null;
            $data['judgeGroups'] = ($this->judgeDivisionId && $selectedDivision?->live_stage !== 'FINAL')
                ? \App\Models\DivisionGroup::where('event_division_id', $this->judgeDivisionId)->orderBy('name')->get()
                : collect();

            $data['operatorLeaderboard'] = ($selectedDivision && $this->scoringMode === 'live')
                ? app(\App\Services\LiveScoreboardService::class)->buildLeaderboard(
                    $selectedDivision,
                    $selectedDivision->live_stage,
                    ($selectedDivision->live_stage !== 'FINAL' && $this->judgeGroupId) ? $this->judgeGroupId : null
                  )
                : collect();

            // Use Registration records directly so all approved participants appear,
            // regardless of whether they have a Rider profile yet.
            $finalistRegIds = \App\Models\DivisionFinalist::whereIn(
                'event_division_id',
                \App\Models\EventDivision::where('event_id', $this->judgeEventId)->where('live_stage', 'FINAL')->pluck('id')
            )->pluck('registration_id');

            $data['judgeRiders'] = $this->judgeEventId
                ? Registration::where('event_id', $this->judgeEventId)
                    ->where('status', 'APPROVED')
                    ->when($this->judgeDivisionId, function ($q) use ($finalistRegIds, $selectedDivision) {
                        $q->where('division_id', $this->judgeDivisionId);
                        if ($selectedDivision?->live_stage === 'FINAL') {
                            $q->whereIn('id', $finalistRegIds);
                        } elseif ($this->judgeGroupId) {
                            $q->where('division_group_id', $this->judgeGroupId);
                        }
                    })
                    ->when(!$this->judgeDivisionId, function ($q) use ($finalistRegIds) {
                        $q->where(function ($q2) use ($finalistRegIds) {
                            $q2->whereDoesntHave('division', fn ($q3) => $q3->where('live_stage', 'FINAL'))
                               ->orWhereIn('id', $finalistRegIds);
                        });
                    })
                    ->with('division')
                    ->orderBy('name')
                    ->get()
                : collect();

            // Other judges' live scores for current rider/run
            $resolvedLiveRiderId = ($this->scoringMode === 'live' && $this->liveRiderId)
                ? $this->resolveRiderIdFromRegistration($this->liveRiderId)
                : null;
            $selectedLiveStage = Registration::find($this->liveRiderId)?->division?->live_stage ?? 'QUALIFICATION';
            $data['isBestTrickPhase'] = Registration::find($this->liveRiderId)?->division?->best_trick_active ?? false;

            if ($this->scoringMode === 'live' && $this->judgeEventId && $resolvedLiveRiderId) {
                $data['otherJudgeScores'] = JudgeScore::where('event_id', $this->judgeEventId)
                    ->where('rider_id', $resolvedLiveRiderId)
                    ->where('run_number', $this->liveRunNumber)
                    ->where('scoring_mode', $data['isBestTrickPhase'] ? 'BEST_TRICK' : 'LIVE')
                    ->where('live_stage', $selectedLiveStage)
                    ->with(['judge', 'scoreDetails'])
                    ->get();
            } else {
                $data['otherJudgeScores'] = collect();
            }
            $data['riderAlreadyRan'] = $data['otherJudgeScores']->isNotEmpty();

            // HEAD JUDGE / OPERATOR: live session — status per judge
            $data['liveJudgeScores'] = collect();
            $data['assignedJudges']  = collect();
            if ($this->canControlLiveSession() && $this->judgeEventId) {
                $liveEvent = $data['activeEvent'];
                if ($liveEvent?->live_rider_id) {
                    $data['liveJudgeScores'] = JudgeScore::where('event_id', $this->judgeEventId)
                        ->where('rider_id', $liveEvent->live_rider_id)
                        ->where('run_number', $liveEvent->live_run_number)
                        ->where('scoring_mode', $this->currentIsBestTrick($liveEvent) ? 'BEST_TRICK' : 'LIVE')
                        ->where('live_stage', $this->currentLiveStage($liveEvent))
                        ->with(['judge', 'scoreDetails'])
                        ->get();
                }
                $data['assignedJudges'] = EventJudgeAssignment::where('event_id', $this->judgeEventId)
                    ->with('user')
                    ->get();
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
