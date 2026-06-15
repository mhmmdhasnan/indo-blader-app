<?php

namespace App\Livewire\Admin;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\BattleSubmission;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventJudgeAssignment;
use App\Models\JudgeScore;
use App\Models\QualificationMatch;
use App\Models\QualificationRound;
use App\Models\Ranking;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\RiderCategory;
use App\Models\ScoringCriterion;
use App\Models\Trick;
use App\Models\User;
use App\Services\BracketService;
use App\Services\NotificationService;
use App\Services\QualificationService;
use App\Services\RankingService;
use App\Services\ScoringService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Admin — Indo Blader')]
class Dashboard extends Component
{
    public string $view = 'overview';

    // Judging state
    public array  $criteriaScores    = [];
    public array  $criteriaScoresB   = [];
    public bool   $scoreSubmitted    = false;
    public int    $judgeEventId     = 0;
    public string $scoringMode      = 'live';
    public string $koMatchType      = 'QUALIFICATION';
    public int    $koMatchId        = 0;
    public int    $liveRiderId      = 0;
    public int    $liveRunNumber    = 1;

    // Category review
    public int    $moveToCategoryId = 0;
    public string $categoryNotes    = '';

    // Trick management
    public string $trickName        = '';
    public string $trickDifficulty  = 'Medium';
    public string $trickDescription = '';

    // Qualification management
    public int    $selectedEventId     = 0;
    public int    $selectedQualRoundId = 0;
    public string $newRoundName        = '';
    public string $pairingType         = 'MANUAL';
    public int    $qualTrickId         = 0;
    public int    $manualRiderAId      = 0;
    public int    $manualRiderBId      = 0;

    // Bracket management
    public string $bracketType = 'SINGLE_ELIMINATION';

    // Qualification editing
    public int    $editQualRoundId   = 0;
    public string $editQualRoundName = '';
    public string $editQualPairing   = 'MANUAL';

    // Submission review
    public string $submissionFeedback = '';

    // Manual bracket setup
    public string $bracketMode       = 'auto';   // auto | manual
    public int    $manualQfCount     = 4;         // how many QF matches (2,4,8)
    public array  $slotAssignments   = [];        // [matchId => ['a' => regId, 'b' => regId]]

    // Scoring criteria management
    public string $criterionName    = '';
    public string $criterionKey     = '';
    public int    $criterionOrder   = 0;
    public int    $editCriterionId  = 0;

    // Event scoring criteria assignment
    public int    $scEventId        = 0;
    public int    $scCriterionId    = 0;
    public string $scAppliesTo      = 'BOTH';
    public int    $scOrder          = 0;

    // Event judge assignment
    public int    $jaEventId        = 0;
    public int    $jaJudgeUserId    = 0;
    public string $jaScoringMode    = 'BOTH';

    // Event CRUD
    public bool   $evEditing    = false;
    public int    $evId         = 0;
    public string $evTitle      = '';
    public string $evEdition    = '';
    public string $evCity       = '';
    public string $evVenue      = '';
    public string $evDate       = '';
    public string $evDateLabel  = '';
    public string $evSlug       = '';
    public string $evStatus     = 'SOON';
    public array  $evCategories = [];
    public int    $evPrize      = 5000000;
    public int    $evSlots      = 32;
    public string $evBlurb      = '';
    public bool   $evFeatured   = false;

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

        app(ScoringService::class)->submitScore($score, $this->criteriaScores);

        $this->scoreSubmitted = true;
    }

    public function submitKnockoutScore(): void
    {
        if (!$this->koMatchId) return;

        if ($this->koMatchType === 'BRACKET') {
            $match  = BracketMatch::findOrFail($this->koMatchId);
            $totalA = app(ScoringService::class)->calculateTotal($this->criteriaScores);
            $totalB = app(ScoringService::class)->calculateTotal($this->criteriaScoresB);
            $match->update(['score_a' => $totalA, 'score_b' => $totalB]);
        }

        $this->scoreSubmitted = true;
    }

    public function resetScore(): void
    {
        $this->scoreSubmitted  = false;
        $this->criteriaScores  = [];
        $this->criteriaScoresB = [];
    }

    // ─── Event CRUD ───────────────────────────────────────────────────────────

    public function openCreateEvent(): void
    {
        $this->evEditing    = false;
        $this->evId         = 0;
        $this->evTitle      = '';
        $this->evEdition    = '';
        $this->evCity       = '';
        $this->evVenue      = '';
        $this->evDate       = '';
        $this->evDateLabel  = '';
        $this->evSlug       = '';
        $this->evStatus     = 'SOON';
        $this->evCategories = [];
        $this->evPrize      = 5000000;
        $this->evSlots      = 32;
        $this->evBlurb      = '';
        $this->evFeatured   = false;
        $this->evEditing    = true;
    }

    public function openEditEvent(int $id): void
    {
        $ev = Event::findOrFail($id);
        $this->evId         = $ev->id;
        $this->evTitle      = $ev->title;
        $this->evEdition    = $ev->edition;
        $this->evCity       = $ev->city;
        $this->evVenue      = $ev->venue;
        $this->evDate       = $ev->date->format('Y-m-d');
        $this->evDateLabel  = $ev->date_label;
        $this->evSlug       = $ev->slug;
        $this->evStatus     = $ev->status;
        $this->evCategories = $ev->categories ?? [];
        $this->evPrize      = (int) $ev->prize;
        $this->evSlots      = $ev->slots;
        $this->evBlurb      = $ev->blurb ?? '';
        $this->evFeatured   = (bool) $ev->featured;
        $this->evEditing    = true;
    }

    public function saveEvent(): void
    {
        $this->validate([
            'evTitle'  => 'required|string|max:120',
            'evCity'   => 'required|string|max:80',
            'evVenue'  => 'required|string|max:120',
            'evDate'   => 'required|date',
            'evSlug'   => 'required|alpha_dash|max:80',
            'evStatus' => 'required|in:SOON,OPEN,CLOSING,FULL,LIVE,CLOSED',
            'evPrize'  => 'required|integer|min:0',
            'evSlots'  => 'required|integer|min:1',
        ], [], [
            'evTitle'  => 'title',
            'evCity'   => 'city',
            'evVenue'  => 'venue',
            'evDate'   => 'date',
            'evSlug'   => 'slug',
            'evStatus' => 'status',
            'evPrize'  => 'prize',
            'evSlots'  => 'slots',
        ]);

        $data = [
            'title'      => $this->evTitle,
            'edition'    => $this->evEdition,
            'city'       => $this->evCity,
            'venue'      => $this->evVenue,
            'date'       => $this->evDate,
            'date_label' => $this->evDateLabel,
            'slug'       => $this->evSlug,
            'status'     => $this->evStatus,
            'categories' => $this->evCategories,
            'prize'      => $this->evPrize,
            'slots'      => $this->evSlots,
            'blurb'      => $this->evBlurb,
            'featured'   => $this->evFeatured,
        ];

        if ($this->evId) {
            Event::findOrFail($this->evId)->update($data);
        } else {
            $data['filled'] = 0;
            Event::create($data);
        }

        $this->evEditing = false;
        $this->evId      = 0;
    }

    public function deleteEvent(int $id): void
    {
        Event::findOrFail($id)->delete();
    }

    public function cancelEvent(): void
    {
        $this->evEditing = false;
        $this->evId      = 0;
    }

    // ─── Registration ─────────────────────────────────────────────────────────

    public function approveRegistration(int $id): void
    {
        $reg = Registration::findOrFail($id);
        $reg->update(['status' => 'APPROVED']);
        NotificationService::send($reg, 'registration_approved', 'Registration Approved',
            "Your registration for {$reg->event->title} has been approved. Entry: {$reg->entry_code}.");
    }

    public function rejectRegistration(int $id): void
    {
        Registration::findOrFail($id)->update(['status' => 'REJECTED']);
    }

    public function pendingRegistration(int $id): void
    {
        Registration::findOrFail($id)->update(['status' => 'PENDING']);
    }

    public function verifyPayment(int $id): void
    {
        Registration::findOrFail($id)->update(['payment_status' => 'VERIFIED']);
    }

    // ─── Category Management ──────────────────────────────────────────────────

    public function approveCategoryAssignment(int $ridCatId, string $notes = ''): void
    {
        $ridCat = RiderCategory::with(['registration', 'category'])->findOrFail($ridCatId);
        $ridCat->update(['status' => 'APPROVED', 'reviewed_at' => now()]);
        $ridCat->reviewLogs()->create([
            'action'         => 'APPROVED',
            'to_category_id' => $ridCat->category_id,
            'notes'          => $notes ?: null,
        ]);
        NotificationService::send($ridCat->registration, 'registration_approved',
            'Category Approved',
            "Your category assignment ({$ridCat->category->name}) has been approved.");
    }

    public function rejectCategoryAssignment(int $ridCatId, string $notes = ''): void
    {
        $ridCat = RiderCategory::with(['registration', 'category'])->findOrFail($ridCatId);
        $ridCat->update(['status' => 'REJECTED', 'reviewed_at' => now(), 'notes' => $notes ?: null]);
        $ridCat->reviewLogs()->create([
            'action'           => 'REJECTED',
            'from_category_id' => $ridCat->category_id,
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
            'reviewed_at' => now(),
            'notes'       => $notes ?: null,
        ]);
        $ridCat->reviewLogs()->create([
            'action'           => 'MOVED',
            'from_category_id' => $oldCatId,
            'to_category_id'   => $newCat->id,
            'notes'            => $notes ?: null,
        ]);
        NotificationService::send($ridCat->registration, 'category_changed',
            'Category Updated',
            "Your competition category has been changed to {$newCat->name}.");
    }

    // ─── Trick Management ─────────────────────────────────────────────────────

    public function createTrick(): void
    {
        $this->validate([
            'trickName'        => 'required|string|max:100',
            'trickDifficulty'  => 'required|in:Easy,Medium,Hard,Expert',
            'trickDescription' => 'nullable|string',
        ]);

        Trick::create([
            'name'        => $this->trickName,
            'difficulty'  => $this->trickDifficulty,
            'description' => $this->trickDescription ?: null,
        ]);

        $this->trickName = '';
        $this->trickDescription = '';
        $this->trickDifficulty = 'Medium';
    }

    public function toggleTrickActive(int $id): void
    {
        $trick = Trick::findOrFail($id);
        $trick->update(['is_active' => !$trick->is_active]);
    }

    // ─── Qualification Management ─────────────────────────────────────────────

    public function createQualificationRound(): void
    {
        $this->validate([
            'selectedEventId' => 'required|exists:events,id',
            'newRoundName'    => 'required|string|max:100',
        ]);

        $lastRound = QualificationRound::where('event_id', $this->selectedEventId)->max('round_number');

        QualificationRound::create([
            'event_id'     => $this->selectedEventId,
            'name'         => $this->newRoundName,
            'round_number' => ($lastRound ?? 0) + 1,
            'pairing_type' => $this->pairingType,
        ]);

        $this->newRoundName = '';
    }

    public function randomizePairings(int $roundId): void
    {
        $round = QualificationRound::findOrFail($roundId);
        app(QualificationService::class)->randomizePairings($round);
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

        if ($match->riderA) {
            NotificationService::send($match->riderA, 'trick_assigned', 'Trick Assigned',
                "A required trick has been assigned to your qualification match.");
        }
        if ($match->riderB) {
            NotificationService::send($match->riderB, 'trick_assigned', 'Trick Assigned',
                "A required trick has been assigned to your qualification match.");
        }
        $this->qualTrickId = 0;
    }

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

    // ─── Bracket Management ───────────────────────────────────────────────────

    public function generateBracket(int $eventId): void
    {
        $event = Event::findOrFail($eventId);

        $existing = Bracket::where('event_id', $eventId)->first();
        if ($existing) {
            $this->addError('bracket', 'Bracket untuk event ini sudah ada.');
            return;
        }

        $registrations = Registration::where('event_id', $eventId)
            ->where('status', 'APPROVED')
            ->get();

        if ($registrations->count() < 2) {
            $this->addError('bracket', 'Minimal 2 peserta yang sudah diapprove diperlukan untuk generate bracket. Saat ini: ' . $registrations->count() . ' peserta.');
            return;
        }

        $bracket = Bracket::create([
            'event_id' => $eventId,
            'type'     => $this->bracketType,
        ]);

        $service = app(BracketService::class);

        if ($this->bracketType === 'DOUBLE_ELIMINATION') {
            $service->generateDoubleElimination($bracket, $registrations);
        } else {
            $service->generateSingleElimination($bracket, $registrations);
        }
    }

    public function generateManualBracket(int $eventId): void
    {
        if (!$eventId) return;

        if (Bracket::where('event_id', $eventId)->exists()) {
            $this->addError('bracket', 'Bracket untuk event ini sudah ada.');
            return;
        }

        $bracket  = Bracket::create(['event_id' => $eventId, 'type' => $this->bracketType]);
        $r1Count  = max(1, (int) $this->manualQfCount);

        if ($this->bracketType === 'DOUBLE_ELIMINATION') {
            // UB_R1
            for ($i = 1; $i <= $r1Count; $i++) {
                BracketMatch::create(['bracket_id' => $bracket->id, 'round' => 'UB_R1', 'match_number' => $i, 'status' => 'PENDING']);
            }
            // Remaining rounds via same structure as BracketService::doubleElimRounds()
            $deRounds = match (true) {
                $r1Count >= 8 => [
                    'UB_R2' => $r1Count / 2, 'UB_SF' => $r1Count / 4, 'UB_F' => 1,
                    'LB_R1' => $r1Count / 2, 'LB_R2' => $r1Count / 2,
                    'LB_R3' => $r1Count / 4, 'LB_R4' => $r1Count / 4,
                    'LB_SF' => 1, 'LB_F' => 1, 'GF' => 1,
                ],
                $r1Count === 4 => [
                    'UB_R2' => 2, 'UB_F' => 1,
                    'LB_R1' => 2, 'LB_R2' => 2, 'LB_SF' => 1, 'LB_F' => 1, 'GF' => 1,
                ],
                $r1Count === 2 => ['UB_F' => 1, 'LB_R1' => 1, 'LB_F' => 1, 'GF' => 1],
                default        => ['GF' => 1],
            };
            foreach ($deRounds as $round => $count) {
                for ($i = 1; $i <= $count; $i++) {
                    BracketMatch::create(['bracket_id' => $bracket->id, 'round' => $round, 'match_number' => $i, 'status' => 'PENDING']);
                }
            }
        } else {
            // Single elimination
            $rounds = [];
            if ($r1Count >= 4) $rounds[] = ['round' => 'QF', 'count' => $r1Count];
            if ($r1Count >= 2) $rounds[] = ['round' => 'SF', 'count' => max(1, (int) ($r1Count / 2))];
            $rounds[] = ['round' => 'F', 'count' => 1];

            foreach ($rounds as $r) {
                for ($i = 1; $i <= $r['count']; $i++) {
                    BracketMatch::create(['bracket_id' => $bracket->id, 'round' => $r['round'], 'match_number' => $i, 'status' => 'PENDING']);
                }
            }
        }

        $bracket->update(['status' => 'IN_PROGRESS']);
    }

    public function assignBracketSlot(int $matchId, string $slot, int $regId): void
    {
        $match = BracketMatch::findOrFail($matchId);
        $col   = $slot === 'a' ? 'rider_a_registration_id' : 'rider_b_registration_id';
        $match->update([$col => $regId ?: null]);
    }

    public function advanceBracketWinner(int $matchId, int $winnerRegId): void
    {
        $match  = BracketMatch::with('bracket.event')->findOrFail($matchId);
        $winner = Registration::findOrFail($winnerRegId);

        $service = app(BracketService::class);

        if ($match->bracket->type === 'DOUBLE_ELIMINATION') {
            $service->advanceWinnerDoubleElim($match, $winner);
        } else {
            $service->advanceWinner($match, $winner);
        }

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

    public function completeBracket(int $bracketId): void
    {
        $bracket = Bracket::findOrFail($bracketId);
        $bracket->update(['status' => 'COMPLETED']);
        app(RankingService::class)->calculateForBracket($bracket);
    }

    public function deleteBracket(int $id): void
    {
        Bracket::findOrFail($id)->delete();
    }

    public function deleteBracketMatch(int $id): void
    {
        BracketMatch::findOrFail($id)->delete();
    }

    // ─── Submission Review ────────────────────────────────────────────────────

    public function approveSubmission(int $id): void
    {
        BattleSubmission::findOrFail($id)->update([
            'status'      => 'APPROVED',
            'reviewed_at' => now(),
        ]);
    }

    public function rejectSubmission(int $id, string $feedback = ''): void
    {
        BattleSubmission::findOrFail($id)->update([
            'status'         => 'REJECTED',
            'judge_feedback' => $feedback ?: null,
            'reviewed_at'    => now(),
        ]);
    }

    public function requestReupload(int $id, string $feedback = ''): void
    {
        BattleSubmission::findOrFail($id)->update([
            'status'         => 'NEED_REUPLOAD',
            'judge_feedback' => $feedback ?: null,
            'reviewed_at'    => now(),
        ]);
    }

    // ─── Rankings ─────────────────────────────────────────────────────────────

    public function recalculateRankings(): void
    {
        app(RankingService::class)->rebuildRankingsTable();
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    // ─── Scoring Criteria ─────────────────────────────────────────────────────

    public function saveCriterion(): void
    {
        $this->validate([
            'criterionName'  => 'required|string|max:60',
            'criterionKey'   => 'required|alpha_dash|max:40',
        ]);

        if ($this->editCriterionId) {
            ScoringCriterion::findOrFail($this->editCriterionId)->update([
                'name'          => $this->criterionName,
                'key'           => $this->criterionKey,
                'display_order' => $this->criterionOrder,
            ]);
            $this->editCriterionId = 0;
        } else {
            ScoringCriterion::create([
                'name'          => $this->criterionName,
                'key'           => $this->criterionKey,
                'display_order' => $this->criterionOrder,
                'is_active'     => true,
            ]);
        }
        $this->criterionName  = '';
        $this->criterionKey   = '';
        $this->criterionOrder = 0;
    }

    public function editCriterion(int $id): void
    {
        $c = ScoringCriterion::findOrFail($id);
        $this->editCriterionId = $id;
        $this->criterionName   = $c->name;
        $this->criterionKey    = $c->key;
        $this->criterionOrder  = $c->display_order;
    }

    public function toggleCriterion(int $id): void
    {
        $c = ScoringCriterion::findOrFail($id);
        $c->update(['is_active' => !$c->is_active]);
    }

    public function deleteCriterion(int $id): void
    {
        ScoringCriterion::findOrFail($id)->delete();
    }

    // ─── Event Scoring Criteria Assignment ───────────────────────────────────

    public function assignCriterionToEvent(): void
    {
        if (!$this->scEventId || !$this->scCriterionId) return;

        $event = Event::findOrFail($this->scEventId);
        $event->scoringCriteria()->syncWithoutDetaching([
            $this->scCriterionId => [
                'applies_to'    => $this->scAppliesTo,
                'display_order' => $this->scOrder,
            ],
        ]);
        $this->scCriterionId = 0;
        $this->scOrder       = 0;
    }

    public function removeCriterionFromEvent(int $eventId, int $criterionId): void
    {
        Event::findOrFail($eventId)->scoringCriteria()->detach($criterionId);
    }

    // ─── Event Judge Assignment ───────────────────────────────────────────────

    public function assignJudgeToEvent(): void
    {
        if (!$this->jaEventId || !$this->jaJudgeUserId) return;

        EventJudgeAssignment::updateOrCreate(
            ['event_id' => $this->jaEventId, 'user_id' => $this->jaJudgeUserId],
            ['scoring_mode' => $this->jaScoringMode]
        );
        $this->jaJudgeUserId = 0;
    }

    public function removeJudgeFromEvent(int $assignmentId): void
    {
        EventJudgeAssignment::findOrFail($assignmentId)->delete();
    }

    public function render()
    {
        $registrations = Registration::with('event')->latest()->get();
        $events        = Event::orderBy('date')->get();
        $riders        = Rider::orderByDesc('points')->get();
        $revenue       = $registrations->count() * 350000;

        $data = compact('registrations', 'events', 'riders', 'revenue');
        $data['eventCriteria']        = collect();
        $data['judgeAssignment']      = null;
        $data['otherJudgeScores']     = collect();
        $data['koOtherJudgeScoresA']  = collect();
        $data['koOtherJudgeScoresB']  = collect();

        if ($this->view === 'judging') {
            $mode     = strtoupper($this->scoringMode);
            $criteria = $this->judgeEventId
                ? Event::find($this->judgeEventId)?->criteriaFor($mode) ?? collect()
                : collect();

            if (empty($this->criteriaScores)) {
                foreach ($criteria as $c) {
                    $this->criteriaScores[$c->key]  = 9.0;
                    $this->criteriaScoresB[$c->key] = 9.0;
                }
            }

            $data['eventCriteria']   = $criteria;
            $data['judgeAssignment'] = null;
            $data['otherJudgeScores'] = collect();

            $data['judgeRiders'] = $this->judgeEventId
                ? Rider::whereHas('registrations', fn ($q) => $q->where('event_id', $this->judgeEventId))->orderBy('name')->get()
                : Rider::orderBy('name')->get();

            $data['koApprovedSubmissions'] = collect();

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

        if ($this->view === 'tricks') {
            $data['tricks'] = Trick::orderBy('difficulty')->get();
        }

        if ($this->view === 'qualification') {
            $data['qualificationRounds'] = QualificationRound::with(['event', 'qualificationMatches.riderA', 'qualificationMatches.riderB', 'qualificationMatches.trick', 'qualificationMatches.winner'])
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

        if ($this->view === 'ranking_admin') {
            $data['rankings'] = Ranking::with('rider')->orderBy('national_rank')->get();
        }

        if ($this->view === 'brackets') {
            $data['brackets'] = Bracket::with(['event', 'bracketMatches.riderA', 'bracketMatches.riderB', 'bracketMatches.winner', 'bracketMatches.trick'])
                ->latest()
                ->get();
            $data['tricks'] = Trick::where('is_active', true)->orderBy('name')->get();
        }

        if ($this->view === 'scoring') {
            $data['scoringCriteria']   = ScoringCriterion::orderBy('display_order')->get();
            $data['allCriteria']       = ScoringCriterion::where('is_active', true)->orderBy('display_order')->get();
            $data['judgeUsers']        = User::whereIn('role', ['judge', 'head_judge'])->orderBy('name')->get();
            $data['eventScoringList']  = Event::with(['scoringCriteria'])->orderBy('date')->get();
            $data['judgeAssignments']  = EventJudgeAssignment::with(['event', 'user'])->latest()->get();
        }

        return view('livewire.admin.dashboard', $data);
    }
}
