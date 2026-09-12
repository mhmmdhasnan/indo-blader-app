<?php

namespace App\Livewire\Admin;

use App\Models\Bracket;
use App\Models\BracketMatch;
use App\Models\BattleSubmission;
use App\Models\Category;
use App\Models\DivisionFinalist;
use App\Models\DivisionGroup;
use App\Models\Event;
use App\Models\EventDivision;
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
use App\Services\LiveScoreboardService;
use App\Services\NotificationService;
use App\Services\QualificationService;
use App\Services\RankingService;
use App\Services\ScoringService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Admin — FRAMEBLADESCORE')]
class Dashboard extends Component
{
    use WithFileUploads;
    public string $view = 'overview';

    // Global active event (synced to all sub-selectors)
    public int $activeEventId = 0;

    // Judging state
    public array  $criteriaScores    = [];
    public array  $criteriaScoresB   = [];
    public bool   $scoreSubmitted    = false;
    public int    $judgeEventId     = 0;
    public int    $judgeDivisionId  = 0;
    public int    $judgeGroupId     = 0;
    public string $scoringMode      = 'live';
    public string $koMatchType      = 'QUALIFICATION';
    public int    $koMatchId        = 0;
    public int    $liveRiderId      = 0;
    public int    $liveRunNumber    = 1;
    public ?float $bestTrickScore   = null; // typed 0-20 score for the Best Trick phase

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
    public string $bracketType       = 'SINGLE_ELIMINATION';
    public int    $bracketDivisionId = 0;

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
    public int    $scCriterionId    = 0;
    public string $scAppliesTo      = 'BOTH';
    public int    $scOrder          = 0;

    // Event judge assignment
    public int    $jaJudgeUserId    = 0;
    public string $jaScoringMode    = 'BOTH';

    // Event CRUD
    public bool   $evEditing    = false;
    public int    $evId         = 0;
    public string $evTitle      = '';
    public string $evEdition    = '';
    public string $evCity       = '';
    public string $evVenue      = '';
    public ?float $evLat        = null;
    public ?float $evLng        = null;
    public string $evDate       = '';
    public string $evDateLabel  = '';
    public string $evSlug       = '';
    public string $evStatus     = 'SOON';
    public string $evType       = 'KO';
    public array  $evCategories        = [];
    public int    $evPrize             = 5000000;
    public bool   $evPrizeHidden       = false;
    public string $evBlurb      = '';
    public bool   $evFeatured   = false;
    public        $evBannerFile = null;
    public string $evBannerPath = '';
    public int    $evRunDuration = 60;
    public array  $evRules      = [];
    public array  $evSchedule   = [];

    // Division CRUD
    public int    $divManageEventId = 0;
    public bool   $divEditing       = false;
    public int    $divId            = 0;
    public string $divDiscipline    = '';
    public string $divLevel         = '';
    public ?int   $divSlots         = null;
    public bool   $divUnlimited     = true;

    // Live Score finalist picker
    public int   $finalistPickerDivisionId = 0;
    public array $selectedFinalistRegIds   = [];

    // Live Score qualification groups
    public int    $groupManageDivisionId = 0;
    public string $newGroupName          = '';
    public int    $randomizeGroupCount   = 2;

    // Competition Level CRUD
    public bool   $clEditing     = false;
    public int    $clId          = 0;
    public string $clName        = '';
    public string $clDescription = '';
    public bool   $clIsActive    = true;

    // Registration edit
    public int    $regEditId         = 0;
    public string $regEditName       = '';
    public string $regEditEmail      = '';
    public string $regEditPhone      = '';
    public string $regEditCity       = '';
    public int    $regEditDivisionId = 0;

    // Payment edit
    public int    $payEditId     = 0;
    public string $payEditMethod = 'Transfer';
    public string $payEditStatus = 'PENDING';
    #[\Livewire\Attributes\Validate(['payEditProof' => 'nullable|image|max:4096'])]
    public $payEditProof = null;

    // User CRUD
    public bool   $userEditing  = false;
    public int    $userId       = 0;
    public string $userName     = '';
    public string $userUsername = '';
    public string $userEmail    = '';
    public string $userRole     = 'rider';
    public string $userPassword = '';
    public string $userSearch   = '';

    // Rider directory (universal) + quick add to active event
    public string $riderDirSearch     = '';
    public int    $quickAddUserId     = 0;
    public string $quickAddCity       = '';
    public string $quickAddPhone      = '';
    public string $quickAddDob        = '';
    public int    $quickAddDivisionId = 0;

    // ─── Boot ────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $active = Event::where('status', 'LIVE')->orderBy('date')->first()
            ?? Event::orderByRaw("ABS(DATEDIFF(date, NOW()))")->orderBy('date')->first()
            ?? Event::orderBy('date')->first();

        if ($active) {
            $this->activeEventId   = $active->id;
            $this->judgeEventId    = $active->id;
            $this->selectedEventId = $active->id;
            $this->judgeDivisionId = $active->active_division_id ?? 0;
            $this->judgeGroupId    = $active->active_group_id ?? 0;
        }
    }

    public function updatedActiveEventId(): void
    {
        $this->judgeEventId    = $this->activeEventId;
        $this->selectedEventId = $this->activeEventId;
        $this->scoreSubmitted  = false;
        $this->koMatchId       = 0;
        $this->liveRiderId     = 0;
        $this->judgeDivisionId = 0;
        $this->criteriaScores  = [];
        $this->criteriaScoresB = [];
    }

    public function updatedJudgeEventId(): void
    {
        $event = $this->judgeEventId ? Event::find($this->judgeEventId) : null;
        $this->judgeDivisionId = $event?->active_division_id ?? 0;
        $this->judgeGroupId    = $event?->active_group_id ?? 0;
        $this->liveRiderId     = 0;
        $this->koMatchId       = 0;
        $this->scoreSubmitted  = false;
        $this->criteriaScores  = [];
    }

    public function updatedJudgeDivisionId(): void
    {
        $this->liveRiderId    = 0;
        $this->judgeGroupId   = 0;
        $this->koMatchId      = 0;
        $this->scoreSubmitted = false;
        $this->criteriaScores = [];

        if ($this->judgeEventId) {
            Event::whereKey($this->judgeEventId)->update([
                'active_division_id' => $this->judgeDivisionId ?: null,
                'active_group_id'    => null,
            ]);
        }
    }

    public function updatedJudgeGroupId(): void
    {
        if ($this->judgeEventId) {
            Event::whereKey($this->judgeEventId)->update([
                'active_group_id' => $this->judgeGroupId ?: null,
            ]);
        }
    }

    // ─── Scoring ─────────────────────────────────────────────────────────────

    public function submitScore(): void
    {
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

        $this->scoreSubmitted = true;
        $this->bestTrickScore = null;
    }

    private function resolveRiderIdFromRegistration(int $registrationId): ?int
    {
        $reg = Registration::find($registrationId);
        if (!$reg) return null;

        if ($reg->user_id) {
            $rider = Rider::where('user_id', $reg->user_id)->first();
            if ($rider) return $rider->id;
        }

        $rider = Rider::where('name', $reg->name)->first();
        if ($rider) return $rider->id;

        $age = $reg->dob ? (int) $reg->dob->diffInYears(now()) : 0;
        $rider = Rider::create([
            'user_id'  => $reg->user_id,
            'name'     => $reg->name,
            'nick'     => $reg->name,
            'city'     => $reg->city ?? '-',
            'age'      => $age,
            'category' => in_array($reg->category, ['STREET','PARK','VERT','FLAT','MINIRAMP']) ? $reg->category : 'STREET',
            'stance'   => in_array($reg->stance, ['Regular','Goofy']) ? $reg->stance : 'Regular',
            'slug'     => Str::slug($reg->name . '-' . $reg->id),
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

    public function syncLiveState(): void
    {
        if (auth()->user()->isHeadJudge()) return;

        $event = $this->judgeEventId ? Event::find($this->judgeEventId) : null;
        if (!$event || $event->live_phase !== 'RUNNING') {
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
        if ($this->liveRiderId !== $reg->id || $this->liveRunNumber !== $eventRunNumber) {
            $this->liveRiderId     = $reg->id;
            $this->liveRunNumber   = $eventRunNumber;
            $this->scoreSubmitted  = false;
            $this->criteriaScores  = [];
            $this->criteriaScoresB = [];
            $this->bestTrickScore  = null;
        }
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
        $this->evLat        = null;
        $this->evLng        = null;
        $this->evDate       = '';
        $this->evDateLabel  = '';
        $this->evSlug       = '';
        $this->evStatus     = 'SOON';
        $this->evType       = 'KO';
        $this->evCategories        = [];
        $this->evPrize             = 5000000;
        $this->evPrizeHidden       = false;
        $this->evBlurb             = '';
        $this->evFeatured          = false;
        $this->evBannerFile        = null;
        $this->evBannerPath        = '';
        $this->evRunDuration       = 60;
        $this->evRules             = [];
        $this->evSchedule          = [];
        $this->evEditing           = true;
    }

    public function openEditEvent(int $id): void
    {
        $ev = Event::findOrFail($id);
        $this->evId         = $ev->id;
        $this->evTitle      = $ev->title;
        $this->evEdition    = $ev->edition;
        $this->evCity       = $ev->city;
        $this->evVenue      = $ev->venue;
        $this->evLat        = $ev->latitude;
        $this->evLng        = $ev->longitude;
        $this->evDate       = $ev->date->format('Y-m-d\TH:i');
        $this->evDateLabel  = $ev->date_label;
        $this->evSlug       = $ev->slug;
        $this->evStatus     = $ev->status;
        $this->evType       = $ev->type ?? 'KO';
        $this->evCategories        = $ev->categories ?? [];
        $this->evPrize      = (int) $ev->prize;
        $this->evPrizeHidden = (bool) $ev->prize_hidden;
        $this->evBlurb      = $ev->blurb ?? '';
        $this->evFeatured   = (bool) $ev->featured;
        $this->evBannerFile  = null;
        $this->evBannerPath  = $ev->banner ?? '';
        $this->evRunDuration = $ev->run_duration ?? 60;
        $this->evRules       = $ev->rules ?? [];
        $this->evSchedule    = $ev->schedule ?? [];
        $this->evEditing     = true;
    }

    public function addEvRule(): void
    {
        $this->evRules[] = '';
    }

    public function removeEvRule(int $index): void
    {
        unset($this->evRules[$index]);
        $this->evRules = array_values($this->evRules);
    }

    public function addEvScheduleItem(): void
    {
        $this->evSchedule[] = ['date' => '', 'time' => '', 'title' => '', 'tag' => ''];
    }

    public function removeEvScheduleItem(int $index): void
    {
        unset($this->evSchedule[$index]);
        $this->evSchedule = array_values($this->evSchedule);
    }

    public function saveEvent(): void
    {
        $this->validate([
            'evTitle'      => 'required|string|max:120',
            'evCity'       => 'required|string|max:80',
            'evVenue'      => 'required|string|max:120',
            'evLat'        => 'nullable|numeric|between:-90,90',
            'evLng'        => 'nullable|numeric|between:-180,180',
            'evDate'       => 'required|date',
            'evSlug'       => 'required|alpha_dash|max:80',
            'evStatus'     => 'required|in:SOON,OPEN,CLOSING,FULL,LIVE,CLOSED',
            'evPrize'      => 'required|integer|min:0',
            'evBannerFile' => 'nullable|image|max:5120',
            'evRunDuration'=> 'required|integer|min:30|max:300',
            'evRules'         => 'nullable|array',
            'evRules.*'       => 'nullable|string|max:300',
            'evSchedule'          => 'nullable|array',
            'evSchedule.*.date'   => 'nullable|date',
            'evSchedule.*.time'   => 'nullable|string|max:20',
            'evSchedule.*.title'  => 'nullable|string|max:150',
            'evSchedule.*.tag'    => 'nullable|string|max:30',
        ], [], [
            'evTitle'      => 'title',
            'evCity'       => 'city',
            'evVenue'      => 'venue',
            'evLat'        => 'latitude',
            'evLng'        => 'longitude',
            'evDate'       => 'date',
            'evSlug'       => 'slug',
            'evStatus'     => 'status',
            'evPrize'      => 'prize',
            'evBannerFile' => 'banner',
        ]);

        $rules = array_values(array_filter($this->evRules, fn ($r) => trim((string) $r) !== ''));
        $schedule = array_values(array_filter($this->evSchedule, fn ($s) => trim((string) ($s['title'] ?? '')) !== ''));

        $bannerPath = $this->evBannerPath;
        if ($this->evBannerFile) {
            $bannerPath = $this->evBannerFile->store('banners', 'public');
        }

        $data = [
            'title'      => $this->evTitle,
            'edition'    => $this->evEdition,
            'city'       => $this->evCity,
            'venue'      => $this->evVenue,
            'latitude'   => $this->evLat,
            'longitude'  => $this->evLng,
            'date'       => $this->evDate,
            'date_label' => $this->evDateLabel,
            'slug'       => $this->evSlug,
            'status'     => $this->evStatus,
            'type'       => $this->evType,
            'categories' => $this->evCategories,
            'prize'        => $this->evPrize,
            'prize_hidden' => $this->evPrizeHidden,
            'blurb'      => $this->evBlurb,
            'featured'     => $this->evFeatured,
            'banner'       => $bannerPath ?: null,
            'run_duration' => $this->evRunDuration,
            'rules'        => $rules,
            'schedule'     => $schedule,
        ];

        if ($this->evId) {
            Event::findOrFail($this->evId)->update($data);
        } else {
            Event::create($data);
        }

        $this->evBannerFile = null;
        $this->evEditing    = false;
        $this->evId         = 0;
    }

    public function deleteEvent(int $id): void
    {
        Event::findOrFail($id)->delete();
    }

    public function cancelEvent(): void
    {
        $this->evEditing    = false;
        $this->evId         = 0;
        $this->evBannerFile = null;
        $this->evBannerPath = '';
    }

    // ─── User CRUD ────────────────────────────────────────────────────────────

    public function userNew(): void
    {
        $this->userEditing  = true;
        $this->userId       = 0;
        $this->userName     = '';
        $this->userUsername = '';
        $this->userEmail    = '';
        $this->userRole     = 'rider';
        $this->userPassword = '';
    }

    public function userEdit(int $id): void
    {
        $user               = User::findOrFail($id);
        $this->userEditing  = true;
        $this->userId       = $id;
        $this->userName     = $user->name;
        $this->userUsername = $user->username;
        $this->userEmail    = $user->email;
        $this->userRole     = $user->role;
        $this->userPassword = '';
    }

    public function userSave(): void
    {
        $rules = [
            'userName'     => 'required|string|max:100',
            'userUsername' => 'required|string|max:30|alpha_dash|unique:users,username' . ($this->userId ? ",{$this->userId}" : ''),
            'userEmail'    => 'required|email|unique:users,email' . ($this->userId ? ",{$this->userId}" : ''),
            'userRole'     => 'required|in:admin,head_judge,judge,rider',
        ];
        if (!$this->userId) {
            $rules['userPassword'] = 'required|min:8';
        } elseif ($this->userPassword) {
            $rules['userPassword'] = 'min:8';
        }

        $this->validate($rules, [
            'userName.required'      => 'Nama wajib diisi.',
            'userUsername.required'  => 'Username wajib diisi.',
            'userUsername.alpha_dash' => 'Username hanya boleh huruf, angka, - dan _.',
            'userUsername.unique'    => 'Username sudah dipakai.',
            'userEmail.required'     => 'Email wajib diisi.',
            'userEmail.unique'       => 'Email sudah dipakai.',
            'userPassword.required'  => 'Password wajib diisi untuk user baru.',
            'userPassword.min'       => 'Password minimal 8 karakter.',
        ]);

        $payload = [
            'name'     => $this->userName,
            'username' => $this->userUsername,
            'email'    => $this->userEmail,
            'role'     => $this->userRole,
        ];
        if ($this->userPassword) {
            $payload['password'] = bcrypt($this->userPassword);
        }

        if ($this->userId) {
            User::findOrFail($this->userId)->update($payload);
        } else {
            User::create($payload);
        }

        $this->userEditing  = false;
        $this->userId       = 0;
        $this->userPassword = '';
    }

    public function userCancel(): void
    {
        $this->userEditing  = false;
        $this->userId       = 0;
        $this->userPassword = '';
    }

    public function userDelete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->addError('userDelete', 'Tidak bisa menghapus akun sendiri.');
            return;
        }
        User::findOrFail($id)->delete();
    }

    // ─── Registration ─────────────────────────────────────────────────────────

    public function approveRegistration(int $id): void
    {
        $reg = Registration::findOrFail($id);

        if ($reg->payment_status !== 'VERIFIED') {
            $this->addError('registration_' . $id, 'Payment harus diverifikasi dulu sebelum approve registrasi.');
            return;
        }

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

    public function openEditRegistration(int $id): void
    {
        $reg = Registration::findOrFail($id);
        $this->regEditId         = $id;
        $this->regEditName       = $reg->name;
        $this->regEditEmail      = $reg->email;
        $this->regEditPhone      = $reg->phone;
        $this->regEditCity       = $reg->city;
        $this->regEditDivisionId = $reg->division_id ?? 0;
    }

    public function saveRegistration(): void
    {
        $reg = Registration::findOrFail($this->regEditId);
        $reg->update([
            'name'        => $this->regEditName,
            'email'       => $this->regEditEmail,
            'phone'       => $this->regEditPhone,
            'city'        => $this->regEditCity,
            'division_id' => $this->regEditDivisionId ?: null,
        ]);
        $this->regEditId = 0;
    }

    public function deleteRegistration(int $id): void
    {
        Registration::findOrFail($id)->delete();
    }

    public function openQuickAdd(int $userId): void
    {
        $previous = Registration::where('user_id', $userId)
            ->whereNotNull('city')
            ->latest()
            ->first();

        $this->quickAddUserId     = $userId;
        $this->quickAddCity       = $previous->city ?? '';
        $this->quickAddPhone      = $previous->phone ?? '';
        $this->quickAddDob        = $previous->dob?->format('Y-m-d') ?? '';
        $this->quickAddDivisionId = 0;
        $this->resetErrorBag();
    }

    public function cancelQuickAdd(): void
    {
        $this->quickAddUserId = 0;
    }

    public function quickAddToEvent(): void
    {
        if (!$this->activeEventId) {
            $this->addError('quickAdd', 'Pilih event aktif dulu (di sidebar) sebelum menambahkan rider.');
            return;
        }

        $user = User::findOrFail($this->quickAddUserId);

        $existing = Registration::where('event_id', $this->activeEventId)
            ->where('user_id', $user->id)
            ->where('division_id', $this->quickAddDivisionId ?: null)
            ->exists();

        if ($existing) {
            $message = $this->quickAddDivisionId
                ? "{$user->name} sudah terdaftar di divisi ini."
                : "{$user->name} sudah punya pendaftaran tanpa divisi di event ini.";
            $this->addError('quickAdd', $message);
            return;
        }

        $this->validate([
            'quickAddCity'  => 'required|string|max:100',
            'quickAddPhone' => 'required|string|max:20',
            'quickAddDob'   => 'required|date',
        ], [], [
            'quickAddCity'  => 'kota',
            'quickAddPhone' => 'telepon',
            'quickAddDob'   => 'tanggal lahir',
        ]);

        Rider::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name'     => $user->name,
                'nick'     => $user->name,
                'city'     => $this->quickAddCity,
                'age'      => \Carbon\Carbon::parse($this->quickAddDob)->age,
                'category' => 'STREET',
                'stance'   => 'Regular',
                'slug'     => Str::slug($user->name . '-' . $user->id),
            ]
        );

        Registration::create([
            'user_id'        => $user->id,
            'entry_code'     => 'IB26-' . strtoupper(Str::random(5)),
            'name'           => $user->name,
            'email'          => $user->email,
            'phone'          => $this->quickAddPhone,
            'dob'            => $this->quickAddDob,
            'city'           => $this->quickAddCity,
            'event_id'       => $this->activeEventId,
            'division_id'    => $this->quickAddDivisionId ?: null,
            'experience'     => 'Amateur',
            'ec_name'        => '-',
            'ec_phone'       => '-',
            'ec_relation'    => '-',
            'payment_method' => 'Transfer',
            'payment_status' => 'VERIFIED',
            'status'         => 'APPROVED',
        ]);

        $this->quickAddUserId = 0;
    }

    public function openEditPayment(int $id): void
    {
        $reg = Registration::findOrFail($id);
        $this->payEditId     = $id;
        $this->payEditMethod = $reg->payment_method ?? 'Transfer';
        $this->payEditStatus = $reg->payment_status ?? 'PENDING';
        $this->payEditProof  = null;
    }

    public function savePayment(): void
    {
        $reg = Registration::findOrFail($this->payEditId);
        $data = [
            'payment_method' => $this->payEditMethod,
            'payment_status' => $this->payEditStatus,
        ];
        if ($this->payEditProof) {
            $data['payment_proof'] = $this->payEditProof->store('proofs', 'public');
        }
        $reg->update($data);
        $this->payEditId    = 0;
        $this->payEditProof = null;
    }

    public function deletePayment(int $id): void
    {
        Registration::findOrFail($id)->update([
            'payment_proof'  => null,
            'payment_status' => 'PENDING',
            'payment_method' => null,
        ]);
    }

    public function verifyPayment(int $id): void
    {
        $reg = Registration::findOrFail($id);
        $reg->update(['payment_status' => 'VERIFIED']);
        NotificationService::send($reg, 'payment_verified', 'Payment Verified',
            "Pembayaran kamu untuk {$reg->event->title} sudah terverifikasi. Registrasi sedang diproses.");
    }

    public function rejectPayment(int $id): void
    {
        $reg = Registration::findOrFail($id);
        $reg->update(['payment_status' => 'UNPAID']);
        NotificationService::send($reg, 'payment_rejected', 'Payment Rejected',
            "Bukti transfer untuk {$reg->event->title} tidak valid. Harap upload ulang bukti pembayaran yang benar.");
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

    // ─── Competition Level CRUD ───────────────────────────────────────────────

    public function openCreateLevel(): void
    {
        $this->clId          = 0;
        $this->clName        = '';
        $this->clDescription = '';
        $this->clIsActive    = true;
        $this->clEditing     = true;
    }

    public function openEditLevel(int $id): void
    {
        $level               = Category::findOrFail($id);
        $this->clId          = $level->id;
        $this->clName        = $level->name;
        $this->clDescription = $level->description ?? '';
        $this->clIsActive    = $level->is_active;
        $this->clEditing     = true;
    }

    public function saveLevel(): void
    {
        $this->validate([
            'clName' => 'required|string|max:64',
        ], [], ['clName' => 'name']);

        $data = [
            'name'        => trim($this->clName),
            'description' => trim($this->clDescription) ?: null,
            'is_active'   => $this->clIsActive,
        ];

        if ($this->clId) {
            Category::findOrFail($this->clId)->update($data);
        } else {
            if (Category::where('name', $data['name'])->exists()) {
                $this->addError('clName', 'Level dengan nama ini sudah ada.');
                return;
            }
            Category::create($data);
        }

        $this->clEditing = false;
        $this->clId      = 0;
        $this->clName    = '';
    }

    public function cancelLevel(): void
    {
        $this->clEditing = false;
        $this->clId      = 0;
    }

    public function toggleLevelActive(int $id): void
    {
        $level = Category::findOrFail($id);
        $level->update(['is_active' => !$level->is_active]);
    }

    public function deleteLevel(int $id): void
    {
        $level = Category::findOrFail($id);
        if ($level->riderCategories()->exists()) {
            $this->addError('clDelete', "Level '{$level->name}' tidak bisa dihapus karena sudah dipakai oleh rider.");
            return;
        }
        $level->delete();
    }

    // ─── Division CRUD ────────────────────────────────────────────────────────

    public function manageDivisions(int $eventId): void
    {
        $this->divManageEventId = $this->divManageEventId === $eventId ? 0 : $eventId;
        $this->divEditing       = false;
        $this->divId            = 0;
    }

    public function openCreateDivision(int $eventId): void
    {
        $this->divManageEventId = $eventId;
        $this->divId            = 0;
        $this->divDiscipline    = '';
        $this->divLevel         = '';
        $this->divSlots         = null;
        $this->divUnlimited     = true;
        $this->divEditing       = true;
    }

    public function openEditDivision(int $id): void
    {
        $div                    = EventDivision::findOrFail($id);
        $this->divManageEventId = $div->event_id;
        $this->divId            = $div->id;
        $this->divDiscipline    = $div->discipline ?? '';
        $this->divLevel         = $div->level ?? '';
        $this->divUnlimited     = $div->slots === null;
        $this->divSlots         = $div->slots;
        $this->divEditing       = true;
    }

    private function buildDivisionName(string $eventType, string $discipline, string $level): string
    {
        if ($eventType === 'KO') {
            return $level;
        }
        $disc = ucfirst(strtolower($discipline));
        return trim("$disc $level");
    }

    public function saveDivision(): void
    {
        $event = Event::findOrFail($this->divManageEventId);

        $this->validate([
            'divLevel' => 'required|string',
            ...($event->type === 'LIVE_SCORE' ? ['divDiscipline' => 'required|string'] : []),
        ], [], ['divLevel' => 'level', 'divDiscipline' => 'discipline']);

        $name = $this->buildDivisionName($event->type, $this->divDiscipline, $this->divLevel);

        $data = [
            'event_id'   => $this->divManageEventId,
            'name'       => $name,
            'discipline' => $this->divDiscipline ?: null,
            'level'      => $this->divLevel,
            'slots'      => $this->divUnlimited ? null : max(1, (int) $this->divSlots),
        ];

        if ($this->divId) {
            EventDivision::findOrFail($this->divId)->update($data);
        } else {
            EventDivision::create($data);
        }

        $this->divEditing = false;
        $this->divId      = 0;
    }

    public function cancelDivision(): void
    {
        $this->divEditing = false;
        $this->divId      = 0;
    }

    public function deleteDivision(int $id): void
    {
        $div = EventDivision::findOrFail($id);
        if ($div->registrations()->exists()) {
            $this->addError('divDelete_' . $id, "Divisi '{$div->name}' tidak bisa dihapus, sudah ada registrasi.");
            return;
        }
        $this->divManageEventId = $div->event_id;
        $div->delete();
    }

    // ─── Live Score: Qualification → Final ──────────────────────────────────────

    public function openFinalistPicker(int $divisionId): void
    {
        $this->finalistPickerDivisionId = $divisionId;
        $this->selectedFinalistRegIds   = DivisionFinalist::where('event_division_id', $divisionId)
            ->pluck('registration_id')->toArray();
    }

    public function cancelFinalistPicker(): void
    {
        $this->finalistPickerDivisionId = 0;
        $this->selectedFinalistRegIds   = [];
    }

    public function openFinalPhase(int $divisionId): void
    {
        if (empty($this->selectedFinalistRegIds)) {
            $this->addError('finalist', 'Pilih minimal 1 finalis.');
            return;
        }

        $division = EventDivision::findOrFail($divisionId);

        foreach ($this->selectedFinalistRegIds as $regId) {
            DivisionFinalist::firstOrCreate([
                'event_division_id' => $divisionId,
                'registration_id'   => $regId,
            ]);
        }
        DivisionFinalist::where('event_division_id', $divisionId)
            ->whereNotIn('registration_id', $this->selectedFinalistRegIds)
            ->delete();

        $division->update(['live_stage' => 'FINAL']);

        foreach (Registration::whereIn('id', $this->selectedFinalistRegIds)->get() as $reg) {
            NotificationService::send($reg, 'final_selected', 'Selamat, Anda Lolos Final!',
                "Anda terpilih sebagai finalis divisi {$division->name}. Final akan segera dimulai.");
        }

        $this->cancelFinalistPicker();
    }

    public function reopenQualification(int $divisionId): void
    {
        EventDivision::findOrFail($divisionId)->update(['live_stage' => 'QUALIFICATION']);
    }

    public function startBestTrickPhase(int $divisionId): void
    {
        EventDivision::findOrFail($divisionId)->update(['best_trick_active' => true]);
    }

    public function endBestTrickPhase(int $divisionId): void
    {
        EventDivision::findOrFail($divisionId)->update(['best_trick_active' => false]);
    }

    public function completeLiveFinal(int $divisionId): void
    {
        $division = EventDivision::findOrFail($divisionId);
        if ($division->live_final_completed_at) {
            return;
        }
        app(RankingService::class)->calculateForLiveFinal($division);
    }

    // ─── Live Score: Qualification Groups ───────────────────────────────────────

    public function openGroupManager(int $divisionId): void
    {
        $this->groupManageDivisionId = $divisionId;
        $this->newGroupName          = '';
    }

    public function closeGroupManager(): void
    {
        $this->groupManageDivisionId = 0;
        $this->newGroupName          = '';
    }

    public function createGroup(): void
    {
        $this->validate(['newGroupName' => 'required|string|max:60'], [], ['newGroupName' => 'nama group']);

        DivisionGroup::create([
            'event_division_id' => $this->groupManageDivisionId,
            'name'               => $this->newGroupName,
        ]);

        $this->newGroupName = '';
    }

    public function deleteGroup(int $groupId): void
    {
        $group = DivisionGroup::findOrFail($groupId);
        Registration::where('division_group_id', $group->id)->update(['division_group_id' => null]);
        $group->delete();
    }

    public function assignToGroup(int $registrationId, ?int $groupId): void
    {
        Registration::where('id', $registrationId)->update(['division_group_id' => $groupId ?: null]);
    }

    public function randomizeGroups(int $divisionId): void
    {
        $groups = DivisionGroup::where('event_division_id', $divisionId)->pluck('id');
        if ($groups->isEmpty()) {
            $this->addError('groupRandomize', 'Buat minimal 1 group dulu sebelum diacak.');
            return;
        }

        $regIds = Registration::where('division_id', $divisionId)
            ->where('status', 'APPROVED')
            ->pluck('id')
            ->shuffle()
            ->values();

        foreach ($regIds as $i => $regId) {
            $groupId = $groups[$i % $groups->count()];
            Registration::where('id', $regId)->update(['division_group_id' => $groupId]);
        }
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
        if (!$this->bracketDivisionId) {
            $this->addError('bracket', 'Pilih divisi terlebih dahulu.');
            return;
        }

        $division = EventDivision::findOrFail($this->bracketDivisionId);

        Bracket::where('event_id', $eventId)->where('division_id', $this->bracketDivisionId)->each(fn ($b) => $b->delete());

        $registrations = Registration::where('event_id', $eventId)
            ->where('status', 'APPROVED')
            ->where('division_id', $this->bracketDivisionId)
            ->get();

        if ($registrations->count() < 2) {
            $this->addError('bracket', "Minimal 2 peserta divisi '{$division->name}' yang sudah diapprove. Saat ini: " . $registrations->count() . ' peserta.');
            return;
        }

        $bracket = Bracket::create([
            'event_id'    => $eventId,
            'division_id' => $this->bracketDivisionId,
            'type'        => $this->bracketType,
        ]);

        $service = app(BracketService::class);

        if ($this->bracketType === 'DOUBLE_ELIMINATION') {
            $service->generateDoubleElimination($bracket, $registrations);
        } else {
            $service->generateSingleElimination($bracket, $registrations);
        }

        $this->bracketDivisionId = 0;
    }

    public function generateManualBracket(int $eventId): void
    {
        if (!$eventId) return;

        if (!$this->bracketDivisionId) {
            $this->addError('bracket', 'Pilih divisi terlebih dahulu.');
            return;
        }

        $division = EventDivision::findOrFail($this->bracketDivisionId);

        Bracket::where('event_id', $eventId)->where('division_id', $this->bracketDivisionId)->each(fn ($b) => $b->delete());

        $bracket  = Bracket::create(['event_id' => $eventId, 'division_id' => $this->bracketDivisionId, 'type' => $this->bracketType]);
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
            // Single elimination — explicit structure per slot count
            $rounds = match ((int) $r1Count) {
                8 => [
                    ['round' => 'QF', 'count' => 8],
                    ['round' => 'SF', 'count' => 4],
                    ['round' => 'F',  'count' => 2],
                    ['round' => 'GF', 'count' => 1],
                ],
                4 => [
                    ['round' => 'QF', 'count' => 4],
                    ['round' => 'SF', 'count' => 2],
                    ['round' => 'F',  'count' => 1],
                ],
                2 => [
                    ['round' => 'SF', 'count' => 2],
                    ['round' => 'F',  'count' => 1],
                ],
                default => [
                    ['round' => 'F',  'count' => 1],
                ],
            };

            foreach ($rounds as $r) {
                for ($i = 1; $i <= $r['count']; $i++) {
                    BracketMatch::create(['bracket_id' => $bracket->id, 'round' => $r['round'], 'match_number' => $i, 'status' => 'PENDING']);
                }
            }
        }

        $bracket->update(['status' => 'IN_PROGRESS']);
        $this->bracketDivisionId = 0;
    }

    public function assignBracketSlot(int $matchId, string $slot, int $regId): void
    {
        $match = BracketMatch::findOrFail($matchId);
        $col   = $slot === 'a' ? 'rider_a_registration_id' : 'rider_b_registration_id';
        $match->update([$col => $regId ?: null]);
    }

    public function setMatchDeadline(int $matchId, string $deadline): void
    {
        $match = BracketMatch::findOrFail($matchId);
        $match->update(['submission_deadline' => $deadline ?: null]);
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
        if (!$this->activeEventId || !$this->scCriterionId) return;

        Event::findOrFail($this->activeEventId)->scoringCriteria()->syncWithoutDetaching([
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
        if (!$this->activeEventId || !$this->jaJudgeUserId) return;

        EventJudgeAssignment::updateOrCreate(
            ['event_id' => $this->activeEventId, 'user_id' => $this->jaJudgeUserId],
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
        $eid    = $this->activeEventId ?: null;
        $events = Event::with('divisions')->orderBy('date')->get();

        $activeEvent    = $eid ? Event::find($eid) : null;
        $registrations  = Registration::with(['event', 'event.divisions', 'division'])
            ->when($eid, fn ($q) => $q->where('event_id', $eid))
            ->latest()->get();
        $riders         = Rider::whereHas('registrations', fn ($q) => $q->when($eid, fn ($q) => $q->where('event_id', $eid)))
            ->orderByDesc('points')->get();
        $revenue        = $registrations->count() * 350000;

        $competitionLevels = Category::orderBy('name')->get();

        $eventDivisions = $this->divManageEventId
            ? EventDivision::where('event_id', $this->divManageEventId)->orderBy('name')->get()
            : collect();

        // Divisions for the selected event in bracket setup
        $bracketDivisions = $this->selectedEventId
            ? EventDivision::where('event_id', $this->selectedEventId)
                ->where('is_active', true)
                ->whereNotIn('id', Bracket::where('event_id', $this->selectedEventId)->pluck('division_id'))
                ->orderBy('name')->get()
            : collect();

        $data = compact('registrations', 'events', 'riders', 'revenue', 'activeEvent', 'competitionLevels', 'eventDivisions', 'bracketDivisions');
        $data['finalistSections'] = $this->finalistPickerDivisionId
            ? app(LiveScoreboardService::class)->buildQualificationSections(
                EventDivision::findOrFail($this->finalistPickerDivisionId)
              )
            : collect();

        $data['groupManageDivision'] = $this->groupManageDivisionId
            ? EventDivision::findOrFail($this->groupManageDivisionId)
            : null;
        $data['groupManageGroups'] = $this->groupManageDivisionId
            ? DivisionGroup::where('event_division_id', $this->groupManageDivisionId)
                ->withCount('registrations')->orderBy('name')->get()
            : collect();
        $data['groupManageRegistrations'] = $this->groupManageDivisionId
            ? Registration::where('division_id', $this->groupManageDivisionId)
                ->where('status', 'APPROVED')->orderBy('name')->get()
            : collect();
        $data['eventCriteria']       = collect();
        $data['judgeAssignment']     = null;
        $data['otherJudgeScores']    = collect();
        $data['koOtherJudgeScoresA'] = collect();
        $data['koOtherJudgeScoresB'] = collect();

        if ($this->view === 'judging') {
            // selalu ikuti active event
            if ($this->activeEventId && $this->judgeEventId !== $this->activeEventId) {
                $this->judgeEventId    = $this->activeEventId;
                $activeEvent           = Event::find($this->activeEventId);
                $this->judgeDivisionId = $activeEvent?->active_division_id ?? 0;
                $this->judgeGroupId    = $activeEvent?->active_group_id ?? 0;
            }
            $jeid = $this->judgeEventId ?: null;
            $mode = strtoupper($this->scoringMode);
            $criteria = $jeid
                ? Event::find($jeid)?->criteriaFor($mode) ?? collect()
                : collect();

            if (empty($this->criteriaScores)) {
                foreach ($criteria as $c) {
                    $this->criteriaScores[$c->key]  = 90.0;
                    $this->criteriaScoresB[$c->key] = 90.0;
                }
            }

            $data['eventCriteria']  = $criteria;
            $did = $this->judgeDivisionId ?: null;

            $data['judgeDivisions'] = $jeid
                ? EventDivision::where('event_id', $jeid)->where('is_active', true)->orderBy('name')->get()
                : collect();

            $eventHasDivisions = $jeid ? EventDivision::where('event_id', $jeid)->exists() : false;
            $selectedDivision  = $did ? EventDivision::find($did) : null;

            $data['judgeGroups'] = ($did && $selectedDivision?->live_stage !== 'FINAL')
                ? DivisionGroup::where('event_division_id', $did)->orderBy('name')->get()
                : collect();

            $finalistRegIds = $jeid
                ? DivisionFinalist::whereIn(
                    'event_division_id',
                    EventDivision::where('event_id', $jeid)->where('live_stage', 'FINAL')->pluck('id')
                  )->pluck('registration_id')
                : collect();

            $data['judgeRiders'] = $jeid
                ? Registration::with('division')
                    ->where('event_id', $jeid)
                    ->where('status', 'APPROVED')
                    ->when($did, function ($q) use ($finalistRegIds, $selectedDivision) {
                        $q->where('division_id', $this->judgeDivisionId);
                        if ($selectedDivision?->live_stage === 'FINAL') {
                            $q->whereIn('id', $finalistRegIds);
                        } elseif ($this->judgeGroupId) {
                            $q->where('division_group_id', $this->judgeGroupId);
                        }
                    })
                    ->when(!$did && $eventHasDivisions, function ($q) use ($finalistRegIds) {
                        $q->whereNotNull('division_id')->where(function ($q2) use ($finalistRegIds) {
                            $q2->whereDoesntHave('division', fn ($q3) => $q3->where('live_stage', 'FINAL'))
                               ->orWhereIn('id', $finalistRegIds);
                        });
                    })
                    ->orderBy('name')->get()
                : collect();

            $data['koApprovedSubmissions'] = collect();

            if ($this->scoringMode === 'knockout' && $jeid) {
                if ($this->koMatchType === 'QUALIFICATION') {
                    $data['koMatches'] = QualificationMatch::whereHas('qualificationRound', fn ($q) => $q->where('event_id', $jeid))
                        ->with(['riderA', 'riderB', 'qualificationRound'])
                        ->where('status', 'PENDING')->get();
                } else {
                    $data['koMatches'] = BracketMatch::whereHas('bracket', fn ($q) => $q
                            ->where('event_id', $jeid)
                            ->when($did, fn ($q2) => $q2->where('division_id', $did))
                            ->when(!$did && $eventHasDivisions, fn ($q2) => $q2->whereNotNull('division_id'))
                        )
                        ->with(['riderA', 'riderB', 'bracket.division'])
                        ->where('status', 'PENDING')->get();
                }
            }

            $data['koCurrentMatch'] = $this->koMatchId
                ? ($this->koMatchType === 'QUALIFICATION'
                    ? QualificationMatch::with(['riderA', 'riderB', 'trick'])->find($this->koMatchId)
                    : BracketMatch::with(['riderA', 'riderB', 'trick'])->find($this->koMatchId))
                : null;

            // Other judges' live scores for current rider/run
            $resolvedLiveRiderId = ($this->scoringMode === 'live' && $this->liveRiderId)
                ? $this->resolveRiderIdFromRegistration($this->liveRiderId)
                : null;
            $selectedLiveStage = Registration::find($this->liveRiderId)?->division?->live_stage ?? 'QUALIFICATION';
            $data['isBestTrickPhase'] = Registration::find($this->liveRiderId)?->division?->best_trick_active ?? false;

            if ($this->scoringMode === 'live' && $jeid && $resolvedLiveRiderId) {
                $data['otherJudgeScores'] = JudgeScore::where('event_id', $jeid)
                    ->where('rider_id', $resolvedLiveRiderId)
                    ->where('run_number', $this->liveRunNumber)
                    ->where('scoring_mode', $data['isBestTrickPhase'] ? 'BEST_TRICK' : 'LIVE')
                    ->where('live_stage', $selectedLiveStage)
                    ->with(['judge', 'scoreDetails'])
                    ->get();
            }
            $data['riderAlreadyRan'] = $data['otherJudgeScores']->isNotEmpty();

            // HEAD JUDGE: live session — status per judge
            $data['liveJudgeScores'] = collect();
            $data['assignedJudges']  = collect();
            if (auth()->user()->isHeadJudge() && $jeid) {
                $liveEvent = $data['activeEvent'];
                if ($liveEvent?->live_rider_id) {
                    $data['liveJudgeScores'] = JudgeScore::where('event_id', $jeid)
                        ->where('rider_id', $liveEvent->live_rider_id)
                        ->where('run_number', $liveEvent->live_run_number)
                        ->where('scoring_mode', $this->currentIsBestTrick($liveEvent) ? 'BEST_TRICK' : 'LIVE')
                        ->where('live_stage', $this->currentLiveStage($liveEvent))
                        ->with(['judge', 'scoreDetails'])
                        ->get();
                }
                $data['assignedJudges'] = EventJudgeAssignment::where('event_id', $jeid)
                    ->with('user')
                    ->get();
            }
        }

        if ($this->view === 'categories') {
            $data['pendingCategoryAssignments'] = RiderCategory::with(['registration.event', 'category'])
                ->where('status', 'PENDING')
                ->whereHas('registration', fn ($q) => $q->where('status', 'APPROVED')
                    ->when($eid, fn ($q) => $q->where('event_id', $eid)))
                ->latest()->get();
            $data['allCategories'] = Category::all();
        }

        if ($this->view === 'tricks') {
            $data['tricks'] = Trick::orderBy('difficulty')->get();
        }

        if ($this->view === 'qualification') {
            $data['qualificationRounds'] = QualificationRound::with(['event', 'qualificationMatches.riderA', 'qualificationMatches.riderB', 'qualificationMatches.trick', 'qualificationMatches.winner'])
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

        if ($this->view === 'ranking_admin') {
            $data['rankings'] = Ranking::with('rider')->orderBy('national_rank')->get();
        }

        if ($this->view === 'brackets') {
            $data['brackets'] = Bracket::with(['event', 'division', 'bracketMatches.riderA', 'bracketMatches.riderB', 'bracketMatches.winner', 'bracketMatches.trick'])
                ->when($eid, fn ($q) => $q->where('event_id', $eid))
                ->latest()->get();
            $data['tricks'] = Trick::where('is_active', true)->orderBy('name')->get();
        }

        if ($this->view === 'scoring') {
            $data['scoringCriteria']  = ScoringCriterion::orderBy('display_order')->get();
            $data['allCriteria']      = ScoringCriterion::where('is_active', true)->orderBy('display_order')->get();
            $data['judgeUsers']       = User::whereIn('role', ['judge', 'head_judge'])->orderBy('name')->get();
            $data['activeEventCriteria'] = $eid
                ? Event::with('scoringCriteria')->find($eid)
                : null;
            $data['judgeAssignments'] = $eid
                ? EventJudgeAssignment::with('user')->where('event_id', $eid)->latest()->get()
                : collect();
        }

        if ($this->view === 'users') {
            $search         = trim($this->userSearch);
            $data['users']  = User::when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->orderBy('role')->orderBy('name')->get();
        }

        if ($this->view === 'riders_all') {
            $search = trim($this->riderDirSearch);

            $registrationsByUser = $eid
                ? Registration::where('event_id', $eid)->whereNotNull('user_id')->with('division')->get()->groupBy('user_id')
                : collect();

            $data['riderDirectory']         = User::where('role', 'rider')
                ->with('rider')
                ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->orderBy('name')->get();
            $data['registrationsByUser'] = $registrationsByUser;
        }

        return view('livewire.admin.dashboard', $data);
    }
}
