<?php

namespace App\Livewire\Admin;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\BattleSubmission;
use App\Models\Category;
use App\Models\Event;
use App\Models\JudgeScore;
use App\Models\QualificationMatch;
use App\Models\QualificationRound;
use App\Models\Ranking;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\RiderCategory;
use App\Models\Trick;
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
    public float  $judgeExec        = 9.2;
    public float  $judgeStyle       = 8.9;
    public float  $judgeCreativity  = 9.4;
    public float  $judgeDiff        = 9.5;
    public float  $judgeConsistency = 9.0;
    public bool   $scoreSubmitted   = false;
    public int    $judgeEventId     = 0;
    public string $scoringMode      = 'live';     // live | knockout
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
            $match = BracketMatch::findOrFail($this->koMatchId);
            $total = round(($this->judgeExec + $this->judgeStyle + $this->judgeCreativity + $this->judgeDiff + $this->judgeConsistency) / 5 * 10, 1);
            // Score goes to whoever is being judged — store in score_a (rider A perspective for now)
            // Judge sees context and picks winner manually
            $match->update(['score_a' => $total]);
        }

        $this->scoreSubmitted = true;
    }

    public function resetScore(): void
    {
        $this->scoreSubmitted = false;
        $this->judgeExec      = 9.2;
        $this->judgeStyle     = 8.9;
        $this->judgeCreativity = 9.4;
        $this->judgeDiff      = 9.5;
        $this->judgeConsistency = 9.0;
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

    public function approveCategoryAssignment(int $ridCatId): void
    {
        $ridCat = RiderCategory::with(['registration', 'category'])->findOrFail($ridCatId);
        $ridCat->update([
            'status'      => 'APPROVED',
            'reviewed_at' => now(),
        ]);
        $ridCat->reviewLogs()->create([
            'action'          => 'APPROVED',
            'to_category_id'  => $ridCat->category_id,
            'notes'           => $this->categoryNotes ?: null,
        ]);
        NotificationService::send($ridCat->registration, 'registration_approved',
            'Category Approved',
            "Your category assignment ({$ridCat->category->name}) has been approved.");
        $this->categoryNotes = '';
    }

    public function rejectCategoryAssignment(int $ridCatId): void
    {
        $ridCat = RiderCategory::with(['registration', 'category'])->findOrFail($ridCatId);
        $ridCat->update([
            'status'      => 'REJECTED',
            'reviewed_at' => now(),
            'notes'       => $this->categoryNotes ?: null,
        ]);
        $ridCat->reviewLogs()->create([
            'action'            => 'REJECTED',
            'from_category_id'  => $ridCat->category_id,
            'notes'             => $this->categoryNotes ?: null,
        ]);
        $this->categoryNotes = '';
    }

    public function moveCategoryAssignment(int $ridCatId): void
    {
        if (!$this->moveToCategoryId) return;
        $ridCat  = RiderCategory::with(['registration', 'category'])->findOrFail($ridCatId);
        $newCat  = Category::findOrFail($this->moveToCategoryId);
        $oldCatId = $ridCat->category_id;

        $ridCat->update([
            'category_id' => $newCat->id,
            'status'      => 'MOVED',
            'reviewed_at' => now(),
            'notes'       => $this->categoryNotes ?: null,
        ]);
        $ridCat->reviewLogs()->create([
            'action'            => 'MOVED',
            'from_category_id'  => $oldCatId,
            'to_category_id'    => $newCat->id,
            'notes'             => $this->categoryNotes ?: null,
        ]);
        NotificationService::send($ridCat->registration, 'category_changed',
            'Category Updated',
            "Your competition category has been changed to {$newCat->name}.");
        $this->categoryNotes    = '';
        $this->moveToCategoryId = 0;
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

    public function addManualPairing(int $roundId): void
    {
        if (!$this->manualRiderAId || !$this->manualRiderBId) return;
        if ($this->manualRiderAId === $this->manualRiderBId) {
            $this->addError('manualRiderAId', 'Rider A dan B tidak boleh sama.');
            return;
        }

        QualificationMatch::create([
            'qualification_round_id'    => $roundId,
            'rider_a_registration_id'   => $this->manualRiderAId,
            'rider_b_registration_id'   => $this->manualRiderBId,
        ]);

        $this->manualRiderAId = 0;
        $this->manualRiderBId = 0;
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
        if ($existing) return;

        $bracket = Bracket::create([
            'event_id' => $eventId,
            'type'     => $this->bracketType,
        ]);

        $registrations = Registration::where('event_id', $eventId)
            ->where('status', 'APPROVED')
            ->get();

        $service = app(BracketService::class);

        if ($this->bracketType === 'DOUBLE_ELIMINATION') {
            $service->generateDoubleElimination($bracket, $registrations);
        } else {
            $service->generateSingleElimination($bracket, $registrations);
        }
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

        if ($this->submissionFeedback) {
            $match->update(['score_a' => null, 'score_b' => null]);
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

    public function rejectSubmission(int $id): void
    {
        BattleSubmission::findOrFail($id)->update([
            'status'         => 'REJECTED',
            'judge_feedback' => $this->submissionFeedback ?: null,
            'reviewed_at'    => now(),
        ]);
        $this->submissionFeedback = '';
    }

    public function requestReupload(int $id): void
    {
        BattleSubmission::findOrFail($id)->update([
            'status'         => 'NEED_REUPLOAD',
            'judge_feedback' => $this->submissionFeedback ?: null,
            'reviewed_at'    => now(),
        ]);
        $this->submissionFeedback = '';
    }

    // ─── Rankings ─────────────────────────────────────────────────────────────

    public function recalculateRankings(): void
    {
        app(RankingService::class)->rebuildRankingsTable();
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $registrations = Registration::with('event')->latest()->get();
        $events        = Event::orderBy('date')->get();
        $riders        = Rider::orderByDesc('points')->get();
        $revenue       = $registrations->count() * 350000;

        $data = compact('registrations', 'events', 'riders', 'revenue');

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

        return view('livewire.admin.dashboard', $data);
    }
}
