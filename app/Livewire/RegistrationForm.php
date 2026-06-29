<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\Registration;
use App\Models\RiderCategory;
use App\Services\NotificationService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Register — Indo Blader')]
class RegistrationForm extends Component
{
    use WithFileUploads;

    public int $step = 0;
    public bool $done = false;
    public string $entryCode = '';

    // Step 0 — Personal
    public string $name        = '';
    public string $email       = '';
    public string $phoneCode   = '+62';
    public string $phoneNumber = '';
    public string $dob         = '';
    public string $city        = '';

    // Step 1 — Division
    public string $eventSlug  = 'nationals';
    public int    $divisionId = 0;

    // Step 2 — Emergency
    public string $ecName      = '';
    public string $ecPhoneCode = '+62';
    public string $ecPhoneNum  = '';
    public string $ecRelation  = '';

    // Step 3 — Payment
    public string $payMethod = 'Transfer';
    public $payFile          = null;
    public bool $agree       = false;

    public array $errors = [];

    public function mount(): void
    {
        if (!auth()->check()) {
            session()->flash('info', 'Kamu harus login dulu sebelum bisa daftar event.');
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $this->eventSlug = Event::orderBy('date')->first()?->slug ?? '';

        $user = auth()->user();
        $this->name  = $user->name;
        $this->email = $user->email;
    }

    public function updatedEventSlug(): void
    {
        $this->divisionId = 0;
    }

    public function next(): void
    {
        $this->errors = [];

        if ($this->step === 0) {
            if (!trim($this->name))                               $this->errors['name']  = 'required';
            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) $this->errors['email'] = 'invalid email';
            if (!preg_match('/^[0-9]{6,}$/', trim($this->phoneNumber))) $this->errors['phone'] = 'min 6 digit';
            if (!$this->dob)                                      $this->errors['dob']   = 'required';
            if (!trim($this->city))                               $this->errors['city']  = 'required';
        }

        if ($this->step === 1) {
            if (!$this->divisionId) {
                $this->errors['division'] = 'pick one';
            } else {
                $event    = Event::where('slug', $this->eventSlug)->first();
                $division = EventDivision::find($this->divisionId);
                if (!$division || $division->event_id !== $event?->id) {
                    $this->errors['division'] = 'invalid';
                }
            }
        }

        if ($this->step === 2) {
            if (!trim($this->ecName))                                   $this->errors['ecName']     = 'required';
            if (!preg_match('/^[0-9]{6,}$/', trim($this->ecPhoneNum))) $this->errors['ecPhone']    = 'min 6 digit';
            if (!trim($this->ecRelation))                               $this->errors['ecRelation'] = 'required';
        }

        if ($this->step === 3) {
            if (!$this->agree) $this->errors['agree'] = 'must agree';
        }

        if (!empty($this->errors)) return;

        if ($this->step === 3) {
            $this->submit();
            return;
        }

        $this->step++;
    }

    public function back(): void
    {
        $this->step = max(0, $this->step - 1);
    }

    private function submit(): void
    {
        $event    = Event::where('slug', $this->eventSlug)->firstOrFail();
        $division = EventDivision::findOrFail($this->divisionId);

        $proofPath = null;
        if ($this->payFile) {
            $proofPath = $this->payFile->store('proofs', 'public');
        }

        $this->entryCode = 'IB26-' . strtoupper(Str::random(5));

        $phone = $this->phoneCode . $this->phoneNumber;

        $reg = Registration::create([
            'entry_code'           => $this->entryCode,
            'name'                 => $this->name,
            'email'                => $this->email,
            'phone'                => $phone,
            'dob'                  => $this->dob,
            'city'                 => $this->city,
            'event_id'             => $event->id,
            'division_id'          => $division->id,
            'category'             => $division->discipline ?? $division->name,
            'competition_category' => $division->level,
            'ec_name'              => $this->ecName,
            'ec_phone'             => $this->ecPhoneCode . $this->ecPhoneNum,
            'ec_relation'          => $this->ecRelation,
            'payment_method'       => 'Transfer',
            'payment_proof'        => $proofPath,
            'payment_status'       => 'PENDING',
            'status'               => 'PENDING',
        ]);

        // Link to global category if level matches
        $cat = $division->level ? Category::where('name', $division->level)->first() : null;
        if ($cat) {
            RiderCategory::create([
                'registration_id' => $reg->id,
                'category_id'     => $cat->id,
                'status'          => 'PENDING',
            ]);
        }

        if (auth()->check() && auth()->user()->isRider()) {
            $reg->update(['user_id' => auth()->user()->id]);
        }

        NotificationService::send(
            $reg,
            'registration_received',
            'Registration Received',
            "Your entry for {$event->title} ({$division->name}) has been received. Entry code: {$this->entryCode}. We'll notify you once your registration is reviewed."
        );

        $division->increment('filled');

        $this->done = true;
        $this->step = 4;
    }

    public function render()
    {
        $events       = Event::orderBy('date')->get();
        $currentEvent = Event::where('slug', $this->eventSlug)->with('divisions')->first();
        $divisions    = $currentEvent
            ? $currentEvent->divisions()->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('livewire.registration-form', compact('events', 'currentEvent', 'divisions'));
    }
}
