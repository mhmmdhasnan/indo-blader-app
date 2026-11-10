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
    public int    $jamRunSynced    = 0; // run heat jamming yang form nilainya sudah di-init di komponen ini
    public int    $judgeDivisionId = 0;
    public int    $judgeGroupId    = 0;
    /** Head Judge: filter heat di Rangkuman nilai. '' = ikuti pilihan Operator, 'all' = semua heat, selain itu id group. */
    public string $summaryHeat     = '';
    public ?float $bestTrickScore  = null; // typed 0-10 score for the Best Trick phase
    public array  $jamScores       = [];   // Jamming heat: [registration_id => [criteria_key => nilai]]
    // Operator: durasi timer jamming (detik) untuk divisi aktif. Sengaja tanpa type `int`:
    // saat input dikosongkan Livewire mengirim '' dan akan meng-unset property bertipe int,
    // yang bikin render berikutnya error "Property not found".
    public $jamDuration            = 0;
    public bool   $scoreReopened   = false; // true when Head Judge reopened this judge's DONE score for revision

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
            $this->liveRunNumber   = (int) ($active->active_run_number ?: 1);
            $this->liveRiderId     = (int) ($active->active_registration_id ?: 0);
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
        $this->jamDuration  = 0;
        $this->phaseModalOpen     = false;   // dialog/panel fase milik divisi sebelumnya ditutup
        $this->finalistPickerOpen = false;
        $this->groupManagerOpen   = false;
        $this->selectedFinalistRegIds = [];

        if ($this->judgeEventId && auth()->user()->isOperator()) {
            Event::find($this->judgeEventId)?->update([
                'active_division_id'     => $this->judgeDivisionId ?: null,
                'active_group_id'        => null,
                'active_registration_id' => null,
            ]);
            $this->liveRiderId = 0;
        }
    }

    /** Operator memilih run / rider: simpan ke event supaya Head Judge & Judge ikut melihatnya. */
    public function updatedLiveRunNumber(): void
    {
        $this->persistOperatorPick();
    }

    public function updatedLiveRiderId(): void
    {
        $this->persistOperatorPick();
    }

    private function persistOperatorPick(): void
    {
        if (!$this->judgeEventId || !auth()->user()->isOperator()) return;

        // Query update (tanpa observer): pilihan ini tidak perlu mem-broadcast ke layar publik.
        Event::whereKey($this->judgeEventId)->update([
            'active_run_number'      => $this->liveRunNumber ?: null,
            'active_registration_id' => $this->liveRiderId ?: null,
        ]);

        // Dorong ke Head Judge / Judge lewat websocket supaya layar mereka berubah seketika.
        try {
            \App\Events\OperatorPickUpdated::dispatch($this->judgeEventId);
        } catch (\Throwable $e) {
            // Websocket mati tidak boleh menggagalkan klik Operator — Head Judge tetap sinkron lewat poll 5 detik.
            report($e);
        }
    }

    /**
     * Pengaman: Head Judge / Judge tidak boleh memulai run dengan pilihan basi dari layarnya sendiri.
     * Kalau Operator sudah memilih run/rider, itulah yang dipakai server.
     */
    private function adoptOperatorPick(): void
    {
        if (!$this->judgeEventId || auth()->user()->isOperator()) return;

        $event = Event::find($this->judgeEventId);
        if ($event?->active_run_number) $this->liveRunNumber = (int) $event->active_run_number;
        if ($event?->active_registration_id) $this->liveRiderId = (int) $event->active_registration_id;
    }

    public function updatedJudgeGroupId(): void
    {
        if ($this->judgeEventId && auth()->user()->isOperator()) {
            Event::find($this->judgeEventId)?->update([
                'active_group_id'        => $this->judgeGroupId ?: null,
                'active_registration_id' => null,
            ]);
            $this->liveRiderId = 0;
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
            // Live: kartu mulai kosong (keypad) — tidak ada nilai awal yang tampak sah. Knockout tetap slider 90.
            $this->criteriaScores[$c->key]  = $mode === 'LIVE' ? null : 90.0;
            $this->criteriaScoresB[$c->key] = 90.0;
        }
    }

    /** Semua kriteria live sudah terisi angka? (nilai kosong tidak boleh dihitung sebagai 0) */
    private function criteriaFilled(array $scores): bool
    {
        $keys = $this->loadCriteria('LIVE')->pluck('key');
        return $keys->isNotEmpty() && $keys->every(fn ($k) => is_numeric($scores[$k] ?? null));
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

    /**
     * Syarat pertandingan boleh jalan: Admin sudah mengatur Kriteria Event (kecuali Best Trick, yang
     * memakai satu nilai langsung) dan meng-assign minimal satu Judge ke event. Null kalau lengkap.
     */
    private function liveSetupProblem(): ?string
    {
        if (!$this->judgeEventId) return null;
        $event = Event::find($this->judgeEventId);
        if (!$event) return null;

        $missing = [];
        $division = $this->judgeDivisionId ? \App\Models\EventDivision::find($this->judgeDivisionId) : null;
        $isBestTrick = (bool) ($division?->best_trick_active);
        if (!$isBestTrick && $event->criteriaFor('LIVE')->isEmpty()) $missing[] = 'Kriteria Event';
        if (!EventJudgeAssignment::where('event_id', $event->id)->exists()) $missing[] = 'Judge Event';

        return $missing
            ? 'Pertandingan belum bisa dimulai: ' . implode(' dan ', $missing) . ' belum di-setup. Minta Admin mengaturnya di Admin → Event.'
            : null;
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

        if (!$this->criteriaFilled($this->criteriaScores)) {
            $this->addError('criteriaScores', 'Isi nilai semua kriteria dulu (tekan OK di keypad).');
            return;
        }

        $stage = Registration::find($this->liveRiderId)?->division?->live_stage ?? 'QUALIFICATION';

        $score = JudgeScore::firstOrCreate(
            [
                'judge_user_id' => auth()->id(),
                'event_id'      => $this->judgeEventId,
                'rider_id'      => $riderId,
                'division_id'   => Registration::find($this->liveRiderId)?->division_id,
                'run_number'    => $this->liveRunNumber,
                'scoring_mode'  => 'LIVE',
                'live_stage'    => $stage,
            ],
            ['status' => 'WAITING']
        );

        app(ScoringService::class)->submitScore($score, $this->criteriaScores);

        $this->scoreSubmitted = true;
        $this->scoreReopened  = false;
    }

    public function submitBestTrickScore(): void
    {
        if (auth()->user()->isOperator()) return;

        if (!$this->judgeEventId || !$this->liveRiderId) {
            $this->addError('judgeEventId', 'Pilih event dan rider terlebih dahulu.');
            return;
        }

        $this->validate([
            'bestTrickScore' => 'required|numeric|min:0|max:10',
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
                'division_id'   => Registration::find($this->liveRiderId)?->division_id,
                'run_number'    => $this->liveRunNumber,
                'scoring_mode'  => 'BEST_TRICK',
                'live_stage'    => 'FINAL',
            ],
            ['total' => $this->bestTrickScore, 'status' => 'DONE']
        );

        $this->scoreSubmitted  = true;
        $this->scoreReopened   = false;
        $this->bestTrickScore  = null;
    }

    /** Foto dari form register jadi avatar rider kalau rider-nya belum punya foto. */
    private function adoptRegistrationPhoto(Rider $rider, Registration $reg): int
    {
        if (!$rider->avatar && $reg->photo) {
            $rider->update(['avatar' => $reg->photo]);
        }

        return $rider->id;
    }

    private function resolveRiderIdFromRegistration(int $registrationId): ?int
    {
        $reg = Registration::find($registrationId);
        if (!$reg) return null;

        // Try to find existing Rider by user_id
        if ($reg->user_id) {
            // Satu akun bisa mendaftarkan banyak rider: hanya pakai Rider milik user kalau namanya sama.
            $rider = Rider::where('user_id', $reg->user_id)->whereRaw('LOWER(name) = ?', [mb_strtolower($reg->name)])->first();
            if ($rider) return $this->adoptRegistrationPhoto($rider, $reg);
        }

        // Try by name
        $rider = Rider::where('name', $reg->name)->first();
        if ($rider) return $this->adoptRegistrationPhoto($rider, $reg);

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
            'avatar'   => $reg->photo,
        ]);

        return $rider->id;
    }

    private function currentLiveStage(Event $event): string
    {
        if (!$event->live_rider_id) return 'QUALIFICATION';

        $liveRider = Rider::find($event->live_rider_id);
        if (!$liveRider) return 'QUALIFICATION';

        $reg = Registration::forLiveRider($event, $liveRider);

        return $reg?->division?->live_stage ?? 'QUALIFICATION';
    }

    /** Division the live rider is currently being scored in (null = registration without a division). */
    private function currentLiveDivisionId(Event $event): ?int
    {
        if (!$event->live_rider_id) return null;

        $liveRider = Rider::find($event->live_rider_id);
        if (!$liveRider) return null;

        return Registration::forLiveRider($event, $liveRider)?->division_id;
    }

    private function currentIsBestTrick(Event $event): bool
    {
        if (!$event->live_rider_id) return false;

        $liveRider = Rider::find($event->live_rider_id);
        if (!$liveRider) return false;

        $reg = Registration::forLiveRider($event, $liveRider);

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

    // ─── Head Judge: annul/reopen a judge's already-submitted score ───────────

    public function reopenJudgeScore(int $scoreId): void
    {
        if (!auth()->user()->isHeadJudge()) {
            $this->addError('judgeEventId', 'Hanya Head Judge yang dapat membuka ulang skor judge.');
            return;
        }

        JudgeScore::findOrFail($scoreId)->update(['status' => 'WAITING']);
    }

    // ─── Head Judge: edit langsung skor judge lain (alternatif dari reopenJudgeScore = minta judge merevisi) ──

    public int    $hjEditScoreId = 0;
    public array  $hjEditValues  = [];      // key kriteria => nilai (Best Trick: ['total' => nilai])
    public ?string $hjEditError  = null;

    public function openHeadJudgeEdit(int $scoreId): void
    {
        $this->hjEditError = null;
        if (!auth()->user()->isHeadJudge()) return;

        $score = JudgeScore::with('scoreDetails')->find($scoreId);
        if (!$score || (int) $score->event_id !== (int) $this->judgeEventId) return;

        $this->hjEditScoreId = $score->id;
        if ($score->scoring_mode === 'BEST_TRICK') {
            $this->hjEditValues = ['total' => $score->total];
        } else {
            $details = $score->scoreDetails->pluck('score', 'criteria');
            $this->hjEditValues = $this->loadCriteria('LIVE')->mapWithKeys(fn ($c) => [$c->key => $details[$c->key] ?? null])->all();
        }
    }

    public function cancelHeadJudgeEdit(): void
    {
        $this->hjEditScoreId = 0;
        $this->hjEditValues  = [];
        $this->hjEditError   = null;
    }

    public function saveHeadJudgeEdit(): void
    {
        $this->hjEditError = null;
        if (!auth()->user()->isHeadJudge()) return;

        $score = JudgeScore::find($this->hjEditScoreId);
        if (!$score || (int) $score->event_id !== (int) $this->judgeEventId) {
            $this->cancelHeadJudgeEdit();
            return;
        }

        if ($score->scoring_mode === 'BEST_TRICK') {
            $v = $this->hjEditValues['total'] ?? null;
            if (!is_numeric($v) || $v < 0 || $v > 10) {
                $this->hjEditError = 'Nilai Best Trick harus 0–10.';
                return;
            }
            $score->update(['total' => round((float) $v, 1), 'status' => 'DONE']);
        } else {
            if (!$this->criteriaFilled($this->hjEditValues)) {
                $this->hjEditError = 'Isi semua kriteria dengan angka.';
                return;
            }
            foreach ($this->hjEditValues as $v) {
                if ($v < 0 || $v > 100) {
                    $this->hjEditError = 'Nilai tiap kriteria harus 0–100.';
                    return;
                }
            }
            app(ScoringService::class)->submitScore($score, $this->hjEditValues);
        }

        $this->cancelHeadJudgeEdit();
    }

    // ─── Operator: fase divisi (Kualifikasi → Final → Best Trick), hanya event aktif ──

    /** Pesan penolakan kartu Fase divisi: properti biasa (bukan ErrorBag) supaya tidak hilang oleh poll 5 detik. */
    public ?string $phaseError    = null;
    public ?string $finalistError = null;

    public bool  $phaseModalOpen         = false;   // dialog lebar "Atur fase divisi"
    public bool  $finalistPickerOpen     = false;
    public array $selectedFinalistRegIds = [];
    public int   $finalistTopN           = 8;
    public int   $finalistHeatCount      = 1;
    public string $finalistSplitMode     = 'auto';   // auto | manual
    public array  $finalistHeatOf        = [];       // manual: id registrasi => nomor heat

    /**
     * Divisi yang boleh diubah fasenya oleh Operator: harus milik event aktif, dan tidak boleh
     * ada run/heat yang sedang berjalan atau sedang ditampilkan (perubahan fase akan mengacaukan layar publik).
     * Mengembalikan null (dan mengisi pesan error) kalau tidak boleh.
     */
    private function phaseDivision(int $divisionId, bool $allowCompleted = false): ?\App\Models\EventDivision
    {
        $this->phaseError = null;
        if (!auth()->user()->isOperator()) return null;

        $division = \App\Models\EventDivision::find($divisionId);
        if (!$division || !$this->judgeEventId || (int) $division->event_id !== (int) $this->judgeEventId) {
            $this->phaseError = ('Divisi ini bukan bagian dari event yang sedang aktif.');
            return null;
        }
        $livePhase = Event::find($this->judgeEventId)?->live_phase;
        if (in_array($livePhase, ['RUNNING', 'NEXT'], true)) {
            $this->phaseError = ($livePhase === 'RUNNING'
                ? 'Selesaikan atau batalkan run/heat yang sedang berjalan dulu sebelum mengubah fase divisi.'
                : 'Batalkan preview rider dulu sebelum mengubah fase divisi.');
            return null;
        }
        if ($livePhase === 'REVEALING') {
            // Skor yang masih tampil di layar publik tidak menghalangi: layar dikembalikan ke leaderboard
            // (sama seperti tombol KEMBALI KE LEADERBOARD) lalu perubahan fase dilanjutkan.
            $this->endSession(false);
        }
        if ($division->live_final_completed_at && !$allowCompleted) {
            $this->phaseError = ('Final divisi ini sudah diselesaikan (ranking sudah dihitung). Buka ulang dulu (BUKA ULANG) untuk mengubahnya.');
            return null;
        }

        return $division;
    }

    /** Heat/rider terpilih milik fase sebelumnya dibersihkan juga di event, supaya Head Judge & layar publik ikut. */
    private function clearHeatPick(): void
    {
        $this->judgeGroupId = 0;
        $this->liveRiderId  = 0;
        Event::find($this->judgeEventId)?->update(['active_group_id' => null, 'active_registration_id' => null]);
    }

    public function openPhaseModal(): void
    {
        if (!auth()->user()->isOperator()) return;

        $this->phaseError  = null;
        $this->phaseModalOpen = true;
    }

    public function closePhaseModal(): void
    {
        $this->phaseModalOpen = false;
        $this->phaseError     = null;
        $this->cancelFinalistPicker();
        $this->groupManagerOpen = false;
    }

    public function openFinalistPicker(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        $this->phaseError = null;
        $this->finalistPickerOpen     = true;
        $this->selectedFinalistRegIds = \App\Models\DivisionFinalist::where('event_division_id', $division->id)
            ->pluck('registration_id')->map(fn ($id) => (int) $id)->all();
        $this->finalistHeatCount      = max(1, \App\Models\DivisionGroup::where('event_division_id', $division->id)->where('stage', 'FINAL')->count());

        // Pembagian heat final yang sudah ada (mis. setelah "kembali ke kualifikasi") dimuat sebagai titik awal manual.
        $this->finalistSplitMode = 'auto';
        $this->finalistHeatOf    = [];
        if ($this->finalistHeatCount > 1) {
            $names = \App\Models\DivisionGroup::where('event_division_id', $division->id)->where('stage', 'FINAL')->orderBy('name')->pluck('id')->values();
            foreach (\App\Models\DivisionFinalist::where('event_division_id', $division->id)->whereNotNull('division_group_id')->get() as $f) {
                $idx = $names->search($f->division_group_id);
                if ($idx !== false) $this->finalistHeatOf[$f->registration_id] = $idx + 1;
            }
            $this->finalistSplitMode = 'manual';
        }
    }

    /** Isi pembagian heat dengan pola otomatis (snake) sebagai titik awal; setelah itu bebas diubah manual. */
    public function autoFillFinalistHeats(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        $ids = array_map('intval', $this->selectedFinalistRegIds);
        $this->finalistHeatOf    = app(\App\Services\DivisionPhaseService::class)
            ->autoAssignment($division, $ids, max(1, $this->finalistHeatCount));
        $this->finalistSplitMode = 'manual';
        $this->finalistError = null;
    }

    /** Pindahkan finalis ke heat final lain (setelah final dibuka, sebelum finalis itu punya nilai final). */
    public function moveFinalistHeat(int $registrationId, int $groupId): void
    {
        $registration = Registration::find($registrationId);
        if (!$registration || !($division = $this->phaseDivision((int) $registration->division_id))) return;

        if ($division->live_stage !== 'FINAL') {
            $this->phaseError = ('Heat final hanya bisa diatur saat divisi di fase Final.');
            return;
        }
        if ($message = app(\App\Services\DivisionPhaseService::class)->moveFinalist($division, $registrationId, $groupId)) {
            $this->phaseError = ($message);
            return;
        }
        $this->phaseError = null;
    }

    public function cancelFinalistPicker(): void
    {
        $this->finalistPickerOpen     = false;
        $this->selectedFinalistRegIds = [];
        $this->finalistHeatOf         = [];
        $this->finalistSplitMode      = 'auto';
        $this->finalistError = null;
    }

    public function autoSelectTopFinalists(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        $this->selectedFinalistRegIds = app(\App\Services\DivisionPhaseService::class)
            ->topRegistrationIds($division, $this->finalistTopN);
    }

    public function openFinalPhase(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        $this->finalistError = null;

        if (empty($this->selectedFinalistRegIds)) {
            $this->finalistError = ('Pilih minimal 1 finalis.');
            return;
        }

        // Hanya peserta divisi ini yang boleh jadi finalis.
        $valid = Registration::where('division_id', $division->id)->where('status', 'APPROVED')
            ->whereIn('id', $this->selectedFinalistRegIds)->pluck('id')->all();
        if (count($valid) !== count(array_unique($this->selectedFinalistRegIds))) {
            $this->finalistError = ('Ada finalis yang bukan peserta approved di divisi ini.');
            return;
        }

        $service = app(\App\Services\DivisionPhaseService::class);
        $heatOf  = null;
        if ($this->finalistSplitMode === 'manual' && $this->finalistHeatCount > 1) {
            $heatOf = [];
            foreach ($valid as $id) $heatOf[(int) $id] = (int) ($this->finalistHeatOf[$id] ?? 0);
            if ($message = $service->validateManualAssignment($valid, min($this->finalistHeatCount, count($valid)), $heatOf)) {
                $this->finalistError = ($message);
                return;
            }
        }

        $service->openFinal($division, $valid, $this->finalistHeatCount, $heatOf);

        $this->clearHeatPick();   // heat lama (kualifikasi) tidak berlaku di final
        $this->cancelFinalistPicker();
    }

    public function reopenQualification(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        app(\App\Services\DivisionPhaseService::class)->reopenQualification($division);
        $this->clearHeatPick();
    }

    public function startBestTrickPhase(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        if ($division->live_stage !== 'FINAL') {
            $this->phaseError = ('Best Trick hanya bisa dimulai saat divisi sudah di fase Final.');
            return;
        }
        app(\App\Services\DivisionPhaseService::class)->setBestTrick($division, true);
    }

    public function endBestTrickPhase(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        app(\App\Services\DivisionPhaseService::class)->setBestTrick($division, false);
    }

    public function completeLiveFinal(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        if ($division->live_stage !== 'FINAL') {
            $this->phaseError = ('Final hanya bisa diselesaikan saat divisi sudah di fase Final.');
            return;
        }
        app(\App\Services\DivisionPhaseService::class)->completeFinal($division);
    }

    /** Batalkan poin final yang sudah dihitung dan kembali ke kualifikasi. */
    public function reopenCompletedFinal(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId, true))) return;

        app(\App\Services\DivisionPhaseService::class)->reopenCompletedFinal($division);
        $this->clearHeatPick();
    }

    /** Hapus semua nilai divisi ini dan kembalikan ke kualifikasi awal. */
    public function resetDivisionScores(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId, true))) return;

        app(\App\Services\DivisionPhaseService::class)->resetScores($division);
        $this->cancelFinalistPicker();
        $this->groupManagerOpen = false;
        $this->clearHeatPick();
    }

    // ─── Operator: kelola group kualifikasi ─────────────────────────────────────

    public bool   $groupManagerOpen = false;
    public string $newGroupName     = '';

    public function openGroupManager(int $divisionId): void
    {
        if (!$this->phaseDivision($divisionId)) return;

        $this->phaseError = null;
        $this->groupManagerOpen = true;
        $this->newGroupName     = '';
    }

    public function closeGroupManager(): void
    {
        $this->groupManagerOpen = false;
        $this->newGroupName     = '';
    }

    public function createGroup(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        $this->validate(['newGroupName' => 'required|string|max:60'], [], ['newGroupName' => 'nama group']);

        app(\App\Services\DivisionPhaseService::class)->createGroup($division, $this->newGroupName);
        $this->newGroupName = '';
    }

    public function deleteGroup(int $groupId): void
    {
        $group = \App\Models\DivisionGroup::where('stage', 'QUALIFICATION')->find($groupId);
        if (!$group || !$this->phaseDivision($group->event_division_id)) return;

        $wasPicked = (int) Event::find($this->judgeEventId)?->active_group_id === (int) $group->id;
        app(\App\Services\DivisionPhaseService::class)->deleteGroup($group);
        if ($wasPicked || (int) $this->judgeGroupId === (int) $group->id) $this->clearHeatPick();
    }

    public function assignToGroup(int $registrationId, ?int $groupId): void
    {
        $registration = Registration::find($registrationId);
        if (!$registration || !($division = $this->phaseDivision((int) $registration->division_id))) return;

        $group = null;
        if ($groupId) {
            $group = \App\Models\DivisionGroup::where('stage', 'QUALIFICATION')
                ->where('event_division_id', $division->id)->find($groupId);
            if (!$group) return;   // group milik divisi lain / bukan group kualifikasi
        }

        app(\App\Services\DivisionPhaseService::class)->assignToGroup($registration, $group);
    }

    public function randomizeGroups(int $divisionId): void
    {
        if (!($division = $this->phaseDivision($divisionId))) return;

        if (!app(\App\Services\DivisionPhaseService::class)->randomizeGroups($division)) {
            $this->phaseError = ('Buat minimal 1 group dulu sebelum diacak.');
        }
    }

    // ─── Operator: public announcements ────────────────────────────────────────

    public function announceQualificationResults(int $divisionId): void
    {
        if (!auth()->user()->isOperator()) return;

        $division = \App\Models\EventDivision::findOrFail($divisionId);
        if (!\App\Models\DivisionFinalist::where('event_division_id', $divisionId)->exists()) {
            $this->addError('judgeEventId', 'Pilih finalis dulu (kartu Fase divisi) sebelum mengumumkan hasil kualifikasi.');
            return;
        }

        $division->update(['qualification_announced_at' => now()]);
    }

    public function unannounceQualificationResults(int $divisionId): void
    {
        if (!auth()->user()->isOperator()) return;
        \App\Models\EventDivision::findOrFail($divisionId)->update(['qualification_announced_at' => null]);
    }

    public function announceFinalResults(int $divisionId): void
    {
        if (!auth()->user()->isOperator()) return;

        $division = \App\Models\EventDivision::findOrFail($divisionId);
        if (!$division->live_final_completed_at) {
            $this->addError('judgeEventId', 'Selesaikan Final (Complete Live Final) dulu di Admin Panel sebelum mengumumkan pemenang.');
            return;
        }

        $division->update(['final_announced_at' => now()]);
    }

    public function unannounceFinalResults(int $divisionId): void
    {
        if (!auth()->user()->isOperator()) return;
        \App\Models\EventDivision::findOrFail($divisionId)->update(['final_announced_at' => null]);
    }

    // ─── Operator: idle screen (tampilkan logo FRAMEBLADESCORE di /live) ──────

    public function showIdleScreen(): void
    {
        if (!auth()->user()->isOperator() || !$this->judgeEventId) return;
        Event::findOrFail($this->judgeEventId)->update(['idle_screen' => true]);
    }

    public function hideIdleScreen(): void
    {
        if (!auth()->user()->isOperator() || !$this->judgeEventId) return;
        Event::findOrFail($this->judgeEventId)->update(['idle_screen' => false]);
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

        // Judge/Head Judge never drive the division/group filter themselves — always
        // mirror whatever the Operator has set as active, on every poll tick, not just
        // when the active event changes.
        if ($event) {
            $mirroredDivisionId = $event->active_division_id ?? 0;
            $mirroredGroupId    = $event->active_group_id ?? 0;
            if ($this->judgeDivisionId !== $mirroredDivisionId || $this->judgeGroupId !== $mirroredGroupId) {
                $this->judgeDivisionId = $mirroredDivisionId;
                $this->judgeGroupId    = $mirroredGroupId;
                $this->initCriteria();
            }
        }

        // Run & rider yang dipilih Operator (belum ada sesi berjalan) ikut dicerminkan.
        if ($event && !$event->live_phase) {
            $pickRun = (int) ($event->active_run_number ?: 1);
            $pickReg = (int) ($event->active_registration_id ?: 0);
            if ($this->liveRunNumber !== $pickRun) {
                $this->liveRunNumber = $pickRun;
            }
            if ($this->liveRiderId !== $pickReg) {
                $this->liveRiderId    = $pickReg;
                $this->bestTrickScore = null;
                $this->scoreReopened  = false;
                $this->initCriteria();
            }
        }

        // Heat Jamming: semua rider heat dinilai bersamaan — siapkan form nilai tiap rider
        // (judge biasa tidak memulai heat sendiri, jadi form-nya disiapkan dari sini).
        if ($event && $event->live_jam && $event->live_phase === 'RUNNING' && ($division = $this->activeJamDivision())) {
            $heatRegs = $this->jamHeatRegistrations($division);
            $jamRun = $event->live_run_number ?: 1;
            // Form di-init ulang saat rider baru muncul atau saat pindah ke run lain (Run 2),
            // supaya nilai run sebelumnya tidak terbawa.
            if ($this->jamRunSynced !== $jamRun || $heatRegs->contains(fn ($reg) => !isset($this->jamScores[$reg->id]))) {
                $this->initJamScores($heatRegs);
                $this->jamRunSynced = $jamRun;
            }
            return;
        }

        if (!$event || !in_array($event->live_phase, ['NEXT', 'RUNNING'], true)) {
            return;
        }

        $liveRider = $event->live_rider_id ? Rider::find($event->live_rider_id) : null;
        if (!$liveRider) return;

        $reg = Registration::forLiveRider($event, $liveRider);

        if (!$reg) return;

        $eventRunNumber = $event->live_run_number ?? 1;
        $riderChanged   = $this->liveRiderId !== $reg->id;
        $runChanged     = $this->liveRunNumber !== $eventRunNumber;

        if ($riderChanged || $runChanged) {
            $this->liveRiderId   = $reg->id;
            $this->liveRunNumber = $eventRunNumber;
            $this->bestTrickScore = null;
            $this->scoreReopened = false;
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
                ->where('division_id', $this->currentLiveDivisionId($event))
                ->where('rider_id', $event->live_rider_id)
                ->where('run_number', $eventRunNumber)
                ->where('scoring_mode', $isBestTrick ? 'BEST_TRICK' : 'LIVE')
                ->where('live_stage', $this->currentLiveStage($event))
                ->where('judge_user_id', auth()->id())
                ->where('status', 'DONE')
                ->exists();

            if ($wasSubmitted && !$this->scoreSubmitted) {
                // Either the run was cancelled & restarted (row deleted — plain reset),
                // or the Head Judge reopened this exact score for revision (row still
                // there, just flipped back to WAITING) — in that case prefill the
                // judge's previous values instead of blanking them back to defaults.
                $existing = JudgeScore::where('event_id', $event->id)
                    ->where('division_id', $this->currentLiveDivisionId($event))
                    ->where('rider_id', $event->live_rider_id)
                    ->where('run_number', $eventRunNumber)
                    ->where('scoring_mode', $isBestTrick ? 'BEST_TRICK' : 'LIVE')
                    ->where('live_stage', $this->currentLiveStage($event))
                    ->where('judge_user_id', auth()->id())
                    ->with('scoreDetails')
                    ->first();

                if ($existing) {
                    $this->scoreReopened = true;
                    if ($isBestTrick) {
                        $this->bestTrickScore = $existing->total;
                    } else {
                        $this->initCriteria();
                        foreach ($existing->scoreDetails as $detail) {
                            $this->criteriaScores[$detail->criteria] = $detail->score;
                        }
                    }
                } else {
                    $this->initCriteria();
                }
            }
        }
    }

    // ─── Live Session Control (Head Judge starts/reveals, Operator sets up) ───

    public function showNextRider(int $registrationId = 0, int $runNumber = 0): void
    {
        if (!$this->canControlLiveSession()) return;

        // Terima pilihan dari browser langsung (bukan cuma state server): update
        // wire:model.live dropdown bisa dibatalkan/ketimpa poll, dan tanpa ini
        // Preview diam-diam memakai rider lama.
        if ($registrationId && Registration::where('id', $registrationId)->where('event_id', $this->judgeEventId)->exists()) {
            $this->liveRiderId = $registrationId;
        }
        if ($runNumber >= 1 && $runNumber <= 3) {
            $this->liveRunNumber = $runNumber;
        }
        // Best Trick: maksimal 2 percobaan.
        if ($this->liveRunNumber > 2 && ($this->liveRiderId ? (bool) Registration::find($this->liveRiderId)?->division?->best_trick_active : false)) {
            $this->liveRunNumber = 2;
        }
        $this->adoptOperatorPick();

        if (!$this->judgeEventId || !$this->liveRiderId) return;

        $riderId = $this->resolveRiderIdFromRegistration($this->liveRiderId);
        if (!$riderId) return;

        Event::findOrFail($this->judgeEventId)->update([
            'live_rider_id'   => $riderId,
            'live_run_number' => $this->liveRunNumber,
            'live_phase'      => 'NEXT',
            'live_jam'        => false,
        ]);
    }

    // ─── Jamming Season (1 heat jalan serentak, semua rider dinilai sekaligus) ─

    private function activeJamDivision(): ?\App\Models\EventDivision
    {
        if (!$this->judgeDivisionId) return null;
        $division = \App\Models\EventDivision::find($this->judgeDivisionId);

        return ($division && $division->format === 'JAMMING' && !$division->best_trick_active) ? $division : null;
    }

    /**
     * Heat (group) terpilih, hanya kalau memang milik tahap divisi sekarang (kualifikasi / final).
     * Id group kualifikasi yang basi tidak boleh terbawa ke tahap final, dan sebaliknya.
     */
    private function stageGroupId(\App\Models\EventDivision $division): ?int
    {
        if (!$this->judgeGroupId) return null;

        return $division->groups()->where('stage', $division->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION')
            ->whereKey($this->judgeGroupId)->exists() ? $this->judgeGroupId : null;
    }

    /** Rider (registrasi) yang ikut heat aktif: heat terpilih (kualifikasi atau final); final tanpa heat = semua finalis. */
    private function jamHeatRegistrations(\App\Models\EventDivision $division): \Illuminate\Support\Collection
    {
        $groupId = $this->stageGroupId($division);

        return app(\App\Services\LiveScoreboardService::class)
            ->registrationsForStage($division, $division->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION', $groupId)
            ->sortBy('name')->values();
    }

    public function updatedJamDuration(): void
    {
        if (!auth()->user()->isOperator()) return;
        $division = $this->activeJamDivision();
        if (!$division) return;

        // Input kosong / <10 detik = masih mengetik, jangan disimpan dulu.
        $seconds = (int) $this->jamDuration;
        if ($seconds < 10) return;

        $division->update(['jam_duration' => min(3600, $seconds)]);
    }

    private function initJamScores(\Illuminate\Support\Collection $registrations): void
    {
        $criteria = $this->loadCriteria('LIVE');
        $this->jamScores = [];
        foreach ($registrations as $reg) {
            foreach ($criteria as $c) {
                $this->jamScores[$reg->id][$c->key] = null;
            }
        }
    }

    public function startJamHeat(): void
    {
        if (!$this->canControlLiveSession()) return;
        $this->adoptOperatorPick();

        $division = $this->activeJamDivision();
        if (!$this->judgeEventId || !$division) {
            $this->addError('judgeDivisionId', 'Pilih divisi Jamming Season terlebih dahulu.');
            return;
        }
        if (!$this->stageGroupId($division) && $division->stageGroups()->isNotEmpty()) {
            $this->addError('judgeGroupId', 'Pilih group (heat) terlebih dahulu.');
            return;
        }

        $registrations = $this->jamHeatRegistrations($division);
        if ($registrations->isEmpty()) {
            $this->addError('judgeGroupId', 'Belum ada rider di heat ini.');
            return;
        }

        if ($problem = $this->liveSetupProblem()) {
            $this->addError('liveSetup', $problem);
            return;
        }

        $jamRun = 1;

        Event::findOrFail($this->judgeEventId)->update([
            'live_rider_id'   => null,
            'live_run_number' => $jamRun,
            'live_phase'      => 'RUNNING',
            'live_jam'        => true,
            'live_duration'   => $division->jam_duration,
            'live_started_at' => now(),
        ]);

        $this->scoreSubmitted = false;
        $this->initJamScores($registrations);
        $this->jamRunSynced = $jamRun;
    }

    /**
     * Preview heat (Jamming) di layar publik: "NEXT UP" berisi kartu semua rider heat itu, sebelum heat dimulai.
     * Dibatalkan lewat endSession (BATAL PREVIEW), atau berlanjut ke RUNNING lewat startJamHeat.
     */
    public function showNextHeat(): void
    {
        if (!$this->canControlLiveSession()) return;
        $this->adoptOperatorPick();

        $division = $this->activeJamDivision();
        if (!$this->judgeEventId || !$division) {
            $this->addError('judgeDivisionId', 'Pilih divisi Jamming Season terlebih dahulu.');
            return;
        }
        if (!$this->stageGroupId($division) && $division->stageGroups()->isNotEmpty()) {
            $this->addError('judgeGroupId', 'Pilih group (heat) terlebih dahulu.');
            return;
        }
        if ($this->jamHeatRegistrations($division)->isEmpty()) {
            $this->addError('judgeGroupId', 'Belum ada rider di heat ini.');
            return;
        }

        $jamRun = 1;

        Event::findOrFail($this->judgeEventId)->update([
            'live_rider_id'   => null,
            'live_run_number' => $jamRun,
            'live_phase'      => 'NEXT',
            'live_jam'        => true,
            'live_duration'   => $division->jam_duration,
            'live_started_at' => null,
        ]);
    }

    /** Judge menyimpan nilai satu rider di heat jamming yang sedang berjalan. */
    public function submitJamScore(int $registrationId): void
    {
        if (auth()->user()->isOperator()) return;

        $event    = Event::find($this->judgeEventId);
        $division = $this->activeJamDivision();
        if (!$event || !$event->live_jam || $event->live_phase !== 'RUNNING' || !$division) return;

        // Hanya rider yang memang ada di heat ini
        if (!$this->jamHeatRegistrations($division)->contains('id', $registrationId)) return;

        if (!$this->criteriaFilled($this->jamScores[$registrationId] ?? [])) {
            $this->addError('jamScores.' . $registrationId, 'Isi nilai semua kriteria dulu (tekan OK di keypad).');
            return;
        }

        $riderId = $this->resolveRiderIdFromRegistration($registrationId);
        if (!$riderId) return;

        $score = JudgeScore::firstOrCreate(
            [
                'judge_user_id' => auth()->id(),
                'event_id'      => $this->judgeEventId,
                'rider_id'      => $riderId,
                'division_id'   => $division->id,
                'run_number'    => $event->live_run_number ?: 1,
                'scoring_mode'  => 'LIVE',
                'live_stage'    => $division->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION',
            ],
            ['status' => 'WAITING']
        );

        // Nilai yang sudah disubmit terkunci — sama seperti live score biasa. Revisi hanya
        // lewat tombol Edit Head Judge (reopenJudgeScore), tidak ada tombol Update di judge.
        if ($score->status === 'DONE') return;

        app(ScoringService::class)->submitScore($score, $this->jamScores[$registrationId] ?? []);
    }

    /**
     * KIRIM SEMUA (layar Judge): kirim setiap rider heat yang kriterianya sudah terisi lengkap.
     * Kartu yang masih kosong dilewati begitu saja (tanpa error) — judge bisa kirim sebagian dulu.
     */
    public function submitAllJamScores(): void
    {
        if (auth()->user()->isOperator()) return;

        $event    = Event::find($this->judgeEventId);
        $division = $this->activeJamDivision();
        if (!$event || !$event->live_jam || $event->live_phase !== 'RUNNING' || !$division) return;

        $this->resetErrorBag();
        foreach ($this->jamHeatRegistrations($division) as $reg) {
            if ($this->criteriaFilled($this->jamScores[$reg->id] ?? [])) {
                $this->submitJamScore($reg->id);   // yang sudah DONE dilewati di dalamnya
            }
        }
    }

    /** Hapus skor heat jamming yang sedang berjalan (dipakai saat heat dibatalkan). */
    private function deleteJamHeatScores(Event $event): void
    {
        $division = \App\Models\EventDivision::find($event->active_division_id);
        if (!$division) return;

        $riderIds = $this->jamHeatRegistrations($division)
            ->map(fn ($reg) => $this->resolveRiderIdFromRegistration($reg->id))
            ->filter()->values();

        JudgeScore::where('event_id', $event->id)
            ->where('division_id', $division->id)
            ->whereIn('rider_id', $riderIds)
            ->where('run_number', $event->live_run_number ?: 1)
            ->where('scoring_mode', 'LIVE')
            ->where('live_stage', $division->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION')
            ->delete();
    }

    public function startRun(): void
    {
        if (!$this->canControlLiveSession()) return;
        $this->adoptOperatorPick();
        if (!$this->judgeEventId || !$this->liveRiderId) {
            $this->addError('liveRiderId', 'Pilih event dan rider terlebih dahulu.');
            return;
        }
        if ($problem = $this->liveSetupProblem()) {
            $this->addError('liveSetup', $problem);
            return;
        }
        $riderId = $this->resolveRiderIdFromRegistration($this->liveRiderId);
        if (!$riderId) {
            $this->addError('liveRiderId', 'Rider tidak ditemukan.');
            return;
        }
        // Best Trick: maksimal 2 percobaan.
        if ($this->liveRunNumber > 2 && Registration::find($this->liveRiderId)?->division?->best_trick_active) {
            $this->liveRunNumber = 2;
        }
        Event::findOrFail($this->judgeEventId)->update([
            'live_rider_id'   => $riderId,
            'live_run_number' => $this->liveRunNumber,
            'live_phase'      => 'RUNNING',
            'live_jam'        => false,
            'live_started_at' => now(),
        ]);

        $this->scoreSubmitted = false;
        $this->scoreReopened  = false;
        $this->bestTrickScore = null;
        $this->initCriteria();
    }

    /** Peringatan "masih ada judge yang belum selesai" — dihitung server saat tombol diklik, bukan dari tampilan yang bisa basi. */
    public ?string $revealWarning = null;

    /**
     * Reveal. Tanpa $force, ditolak (dengan peringatan inline) kalau masih ada judge yang belum selesai;
     * status judge dibaca ulang dari database saat itu juga.
     */
    public function revealScore(bool $force = false): void
    {
        if (!auth()->user()->isHeadJudge()) return;

        $event = Event::findOrFail($this->judgeEventId);

        if (!$force) {
            $pending = $this->pendingJudgeSummary($event);
            if ($pending !== '') {
                $this->revealWarning = $pending;
                return;
            }
        }

        $this->revealWarning = null;
        $event->update(['live_phase' => 'REVEALING']);
    }

    public function cancelRevealWarning(): void
    {
        $this->revealWarning = null;
    }

    /** "Ican 4 rider, Asmo 1 rider" / "Ican" — kosong kalau semua judge sudah selesai. */
    private function pendingJudgeSummary(Event $event): string
    {
        $judges = EventJudgeAssignment::where('event_id', $event->id)->with('user')->get();
        if ($judges->isEmpty() || $event->live_phase !== 'RUNNING') return '';

        if ($event->live_jam) {
            $division = \App\Models\EventDivision::find($event->active_division_id);
            if (!$division) return '';
            $stage    = $division->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION';
            $riderIds = $this->jamHeatRegistrations($division)
                ->map(fn ($reg) => $this->resolveRiderIdFromRegistration($reg->id))->filter()->values();
            $total = $riderIds->count();
            if ($total === 0) return '';

            $done = JudgeScore::where('event_id', $event->id)->where('division_id', $division->id)
                ->whereIn('rider_id', $riderIds)->where('run_number', $event->live_run_number ?: 1)
                ->where('scoring_mode', 'LIVE')->where('live_stage', $stage)->where('status', 'DONE')
                ->selectRaw('judge_user_id, count(*) as c')->groupBy('judge_user_id')->pluck('c', 'judge_user_id');

            return $judges->map(function ($aj) use ($done, $total) {
                $left = $total - min((int) ($done[$aj->user_id] ?? 0), $total);
                return $left > 0 ? ($aj->user?->name ?? '—') . ' ' . $left . ' rider' : null;
            })->filter()->implode(', ');
        }

        $doneIds = JudgeScore::where('event_id', $event->id)
            ->where('division_id', $this->currentLiveDivisionId($event))
            ->where('rider_id', $event->live_rider_id)->where('run_number', $event->live_run_number)
            ->where('scoring_mode', $this->currentIsBestTrick($event) ? 'BEST_TRICK' : 'LIVE')
            ->where('live_stage', $this->currentLiveStage($event))->where('status', 'DONE')
            ->pluck('judge_user_id')->all();

        return $judges->reject(fn ($aj) => in_array($aj->user_id, $doneIds))
            ->map(fn ($aj) => $aj->user?->name ?? '—')->implode(', ');
    }

    /**
     * @param bool $deleteScores Saat membatalkan run/heat yang SEDANG BERJALAN: true = hapus skor yang
     *                           sudah masuk (default), false = batalkan tapi simpan skornya.
     */
    public function endSession(bool $deleteScores = true): void
    {
        if (!$this->canControlLiveSession()) return;

        $event = Event::findOrFail($this->judgeEventId);

        // Membatalkan run yang sedang berjalan (bukan sekadar batal preview atau
        // kembali dari layar reveal) juga menghapus skor yang sudah disubmit
        // untuk run itu, supaya tidak ada skor "nyangkut" kalau rider run ulang.
        if (!$deleteScores) {
            // Dipilih: batalkan sesi tanpa menghapus skor yang sudah masuk.
        } elseif ($event->live_jam && $event->live_phase === 'RUNNING') {
            $this->deleteJamHeatScores($event);
        } elseif ($event->live_phase === 'RUNNING' && $event->live_rider_id && $event->live_run_number) {
            JudgeScore::where('event_id', $event->id)
                ->where('division_id', $this->currentLiveDivisionId($event))
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
            'live_jam'        => false,
            'live_duration'   => null,
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
                    $this->criteriaScores[$c->key]  = $mode === 'LIVE' ? null : 90.0;
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
            $data['judgeGroups'] = $selectedDivision ? $selectedDivision->stageGroups() : collect();

            // Heat final beserta finalisnya, untuk panel "Atur heat final" (hanya saat divisi di fase Final).
            $data['finalHeatOverview'] = ($selectedDivision && $selectedDivision->live_stage === 'FINAL')
                ? app(\App\Services\DivisionPhaseService::class)->finalHeatOverview($selectedDivision) : collect();

            // Kelola group kualifikasi di konsol Operator (hanya dihitung saat panel dibuka).
            $data['phaseGroups']        = ($this->groupManagerOpen && $selectedDivision)
                ? app(\App\Services\DivisionPhaseService::class)->qualificationGroups($selectedDivision) : collect();
            $data['phaseRegistrations'] = ($this->groupManagerOpen && $selectedDivision)
                ? Registration::where('division_id', $selectedDivision->id)->where('status', 'APPROVED')->orderBy('name')->get() : collect();

            // Peringkat kualifikasi untuk pemilih finalis di konsol Operator (hanya dihitung saat pemilih dibuka).
            $data['phaseRanking'] = ($this->finalistPickerOpen && $selectedDivision)
                ? app(\App\Services\DivisionPhaseService::class)->qualificationRanking($selectedDivision)
                : collect();

            $data['operatorLeaderboard']         = collect();
            $data['operatorLeaderboardSections']  = collect();
            if ($selectedDivision && $this->scoringMode === 'live') {
                $scoreboardSvc = app(\App\Services\LiveScoreboardService::class);

                $opGroupId = $data['judgeGroups']->contains('id', $this->judgeGroupId) ? $this->judgeGroupId : null;

                if ($selectedDivision->live_stage !== 'FINAL' && !$opGroupId && $data['judgeGroups']->isNotEmpty()) {
                    // "Semua" group selected and this division has groups — show one
                    // leaderboard table per group instead of one combined table.
                    $data['operatorLeaderboardSections'] = $scoreboardSvc->buildQualificationSections($selectedDivision);
                } else {
                    $data['operatorLeaderboard'] = $scoreboardSvc->buildLeaderboard(
                        $selectedDivision,
                        $selectedDivision->live_stage,
                        $opGroupId
                    );
                }
            }

            // Jamming Season: heat aktif, rider-nya, dan nilai yang sudah disubmit judge ini
            $data['jamDivision']   = $this->activeJamDivision();
            $data['jamHeatRegs']   = collect();
            $data['jamSubmitted']  = [];
            $data['jamAllScores']  = [];
            $data['jamIsLive']     = (bool) ($data['activeEvent']?->live_jam && $data['activeEvent']?->live_phase === 'RUNNING');
            if ($data['jamDivision']) {
                if ($this->jamDuration === 0) {
                    $this->jamDuration = $data['jamDivision']->jam_duration;
                }
                $data['jamHeatRegs'] = $this->jamHeatRegistrations($data['jamDivision']);

                // Heat ini sudah punya nilai untuk nomor run yang akan dimulai? (dipakai untuk konfirmasi MULAI HEAT)
                $ranRiderIds = $data['jamHeatRegs']
                    ->map(fn ($reg) => $this->resolveRiderIdFromRegistration($reg->id))->filter()->values();
                $data['heatAlreadyRan'] = !$data['jamIsLive'] && $ranRiderIds->isNotEmpty()
                    && JudgeScore::where('event_id', $this->judgeEventId)
                        ->where('division_id', $data['jamDivision']->id)
                        ->whereIn('rider_id', $ranRiderIds)
                        ->where('run_number', 1)
                        ->where('scoring_mode', 'LIVE')
                        ->where('live_stage', $data['jamDivision']->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION')
                        ->where('status', 'DONE')
                        ->exists();

                if ($data['jamIsLive'] && !auth()->user()->isOperator()) {
                    $stageForJam = $data['jamDivision']->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION';
                    $jamRun = $data['activeEvent']->live_run_number ?: 1;
                    $jamRiderIds = [];
                    foreach ($data['jamHeatRegs'] as $reg) {
                        $rid = $this->resolveRiderIdFromRegistration($reg->id);
                        $jamRiderIds[$reg->id] = $rid;
                        $data['jamSubmitted'][$reg->id] = $rid && JudgeScore::where('event_id', $this->judgeEventId)
                            ->where('division_id', $data['jamDivision']->id)
                            ->where('rider_id', $rid)->where('run_number', $jamRun)
                            ->where('scoring_mode', 'LIVE')->where('live_stage', $stageForJam)
                            ->where('judge_user_id', auth()->id())->where('status', 'DONE')->exists();
                    }

                    // Nilai semua judge yang sudah submit di heat ini — terlihat oleh judge lain
                    // begitu mereka submit. Kunci = registration id.
                    $allJam = JudgeScore::with('judge')
                        ->where('event_id', $this->judgeEventId)
                        ->where('division_id', $data['jamDivision']->id)
                        ->whereIn('rider_id', array_filter($jamRiderIds))
                        ->where('run_number', $jamRun)
                        ->where('scoring_mode', 'LIVE')->where('live_stage', $stageForJam)
                        ->where('status', 'DONE')
                        ->get()->groupBy('rider_id');
                    $data['jamAllScores'] = collect($jamRiderIds)
                        ->map(fn ($rid) => $rid ? $allJam->get($rid, collect()) : collect())->all();
                }
            }

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
                            // Best Trick melibatkan semua finalis: heat final yang masih terpilih tidak boleh mempersempit daftar rider.
                            if (!$selectedDivision->best_trick_active
                                && ($fg = $selectedDivision->groups()->where('stage', 'FINAL')->whereKey($this->judgeGroupId)->value('id'))) {
                                $q->whereIn('id', \App\Models\DivisionFinalist::where('division_group_id', $fg)->pluck('registration_id'));
                            }
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

            // Riwayat nilai judge ini sendiri (Live + Best Trick), dikelompokkan per divisi —
            // divisi yang sedang aktif dipilih Operator ditaruh paling atas.
            $data['myPastScoresByDivision'] = collect();
            if ($this->judgeEventId && $this->scoringMode === 'live' && !auth()->user()->isOperator()) {
                // Hanya divisi yang sedang dipilih Operator; judge biasa hanya melihat nilainya sendiri.
                $pastDivisionId = $data['activeEvent']?->active_division_id ?: $this->judgeDivisionId;
                $pastScores = JudgeScore::where('judge_user_id', auth()->id())
                    ->where('event_id', $this->judgeEventId)
                    ->whereIn('scoring_mode', ['LIVE', 'BEST_TRICK'])
                    ->where('status', 'DONE')
                    ->when($pastDivisionId, fn ($q) => $q->where('division_id', $pastDivisionId))
                    ->with(['rider', 'division'])
                    ->orderByDesc('updated_at')
                    ->limit(200)
                    ->get();

                $data['myPastScoresByDivision'] = $pastScores
                    ->groupBy(fn ($past) => $past->division->name ?? 'Tanpa Divisi')
                    ->sortBy(fn ($rows, $name) => [$name === ($selectedDivision?->name) ? 0 : 1, $name]);
            }

            // Head Judge: rangkuman nilai SEMUA judge per HEAT untuk divisi yang dipilih Operator.
            // - stage mengikuti divisi (Kualifikasi / Final), jadi nilai stage lain tidak ikut tercampur;
            // - heat (group) yang dipilih Operator → hanya heat itu; "Semua heat" → semua heat, dikelompokkan;
            // - tiap heat diurutkan dari rata-rata tertinggi. Satu baris per rider + run.
            $data['headJudgeSummary'] = collect();
            $data['hjEditCriteria']   = $this->hjEditScoreId ? $this->loadCriteria('LIVE') : collect();
            if ($this->judgeEventId && $this->scoringMode === 'live' && auth()->user()->isHeadJudge() && !auth()->user()->isOperator()) {
                $summaryDivisionId = $data['activeEvent']?->active_division_id ?: $this->judgeDivisionId;
                $summaryDivision   = $summaryDivisionId ? \App\Models\EventDivision::find($summaryDivisionId) : null;
                if ($summaryDivision) {
                    $summaryStage = $summaryDivision->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION';

                    // rider_id → [urutan heat, id group, nama heat] (kualifikasi saja; final = satu pool).
                    $heatOf = [];
                    if ($summaryStage === 'FINAL') {
                        // Final: heat = group finalis (nama "Final Heat 2" → "Heat 2").
                        $finGroups = $summaryDivision->stageGroups()->values();
                        foreach ($summaryDivision->finalists()->whereNotNull('division_group_id')->get() as $fin) {
                            $reg   = Registration::find($fin->registration_id);
                            $rider = $reg ? (($reg->user_id ? Rider::where('user_id', $reg->user_id)->first() : null) ?: Rider::where('name', $reg->name)->first()) : null;
                            $gi    = $finGroups->search(fn ($g) => (int) $g->id === (int) $fin->division_group_id);
                            if ($rider && $gi !== false) {
                                $heatOf[$rider->id] = [$gi, $fin->division_group_id, preg_replace('/^Final\s+/i', '', $finGroups[$gi]->name)];
                            }
                        }
                    }
                    if ($summaryStage === 'QUALIFICATION') {
                        $sections = app(\App\Services\LiveScoreboardService::class)->buildQualificationSections($summaryDivision);
                        foreach ($sections->values() as $order => $sec) {
                            foreach ($sec['leaderboard'] as $lbRow) {
                                if ($lbRow['rider']->id) {
                                    $heatOf[$lbRow['rider']->id] = [$order, $sec['group']?->id, $sec['group']?->name ?? 'TANPA HEAT'];
                                }
                            }
                        }
                    }
                    // Dropdown Head Judge menimpa pilihan Operator: 'all' = semua heat, id = heat itu saja.
                    $pickedGroupId = $this->summaryHeat === 'all'
                        ? 0
                        : ($this->summaryHeat !== '' ? (int) $this->summaryHeat : (int) ($data['activeEvent']?->active_group_id ?: $this->judgeGroupId));
                    $data['summaryHeatOptions'] = $summaryDivision->stageGroups()->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])->values();

                    $data['headJudgeSummary'] = JudgeScore::with(['judge', 'rider'])
                        ->where('event_id', $this->judgeEventId)
                        ->where('division_id', $summaryDivisionId)
                        ->whereIn('scoring_mode', ['LIVE', 'BEST_TRICK'])
                        ->where('live_stage', $summaryStage)
                        ->where('status', 'DONE')
                        ->whereNotNull('judge_user_id')
                        ->get()
                        ->groupBy(fn ($sc) => $sc->rider_id . '|' . $sc->scoring_mode . '|' . $sc->live_stage . '|' . $sc->run_number)
                        ->map(function ($grp) use ($heatOf) {
                            $first = $grp->first();
                            $heat  = $heatOf[$first->rider_id] ?? [999, null, null];

                            return [
                                'rider'      => $first->rider,
                                'mode'       => $first->scoring_mode,
                                'stage'      => $first->live_stage,
                                'run'        => $first->run_number,
                                'heat_order' => $heat[0],
                                'heat_id'    => $heat[1],
                                'heat'       => $heat[2],
                                'judges'     => $grp->map(fn ($sc) => ['id' => $sc->id, 'user_id' => $sc->judge_user_id, 'name' => $sc->judge?->name ?? '—', 'total' => $sc->total])->values(),
                                'avg'        => round($grp->avg('total'), 1),
                            ];
                        })
                        ->when($pickedGroupId, fn ($c) => $c->filter(fn ($r) => (int) $r['heat_id'] === $pickedGroupId))
                        ->sortBy([['heat_order', 'asc'], ['run', 'asc'], ['avg', 'desc']])
                        ->values();
                }
            }

            // Other judges' live scores for current rider/run
            $resolvedLiveRiderId = ($this->scoringMode === 'live' && $this->liveRiderId)
                ? $this->resolveRiderIdFromRegistration($this->liveRiderId)
                : null;
            $selectedLiveStage = Registration::find($this->liveRiderId)?->division?->live_stage ?? 'QUALIFICATION';
            $data['isBestTrickPhase'] = Registration::find($this->liveRiderId)?->division?->best_trick_active ?? false;

            if ($this->scoringMode === 'live' && $this->judgeEventId && $resolvedLiveRiderId) {
                $data['otherJudgeScores'] = JudgeScore::where('event_id', $this->judgeEventId)
                    ->where('division_id', Registration::find($this->liveRiderId)?->division_id)
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
                        ->where('division_id', $this->currentLiveDivisionId($liveEvent))
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
                $data['liveSetupProblem'] = $this->liveSetupProblem();

                // Jamming: progres tiap judge = jumlah rider heat yang sudah dinilai
                $data['jamProgress'] = [];
                if ($data['jamIsLive'] && $data['jamDivision']) {
                    $riderIds = $data['jamHeatRegs']
                        ->map(fn ($reg) => $this->resolveRiderIdFromRegistration($reg->id))->filter()->values();
                    $data['jamProgress'] = JudgeScore::where('event_id', $this->judgeEventId)
                        ->where('division_id', $data['jamDivision']->id)
                        ->whereIn('rider_id', $riderIds)->where('run_number', $data['activeEvent']->live_run_number ?: 1)
                        ->where('scoring_mode', 'LIVE')
                        ->where('live_stage', $data['jamDivision']->live_stage === 'FINAL' ? 'FINAL' : 'QUALIFICATION')
                        ->where('status', 'DONE')
                        ->selectRaw('judge_user_id, count(*) as c')->groupBy('judge_user_id')
                        ->pluck('c', 'judge_user_id')->all();
                }
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
