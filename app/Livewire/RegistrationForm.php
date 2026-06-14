<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
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
    public string $name     = '';
    public string $email    = '';
    public string $phone    = '';
    public string $dob      = '';
    public string $city     = '';
    public string $stance   = 'Regular';

    // Step 1 — Category
    public string $eventSlug = 'nationals';
    public string $category   = '';
    public string $experience = 'Amateur';

    // Step 2 — Emergency
    public string $ecName     = '';
    public string $ecPhone    = '';
    public string $ecRelation = '';

    // Step 3 — Payment
    public string $payMethod = 'Transfer';
    public $payFile          = null;
    public bool $agree       = false;

    public array $errors = [];

    public function mount(): void
    {
        $this->eventSlug = Event::orderBy('date')->first()?->slug ?? 'nationals';
    }

    public function next(): void
    {
        $this->errors = [];

        if ($this->step === 0) {
            if (!trim($this->name))                         $this->errors['name']  = 'required';
            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) $this->errors['email'] = 'invalid email';
            if (!preg_match('/^[0-9+\-\s]{8,}$/', $this->phone)) $this->errors['phone'] = 'invalid';
            if (!$this->dob)                                $this->errors['dob']   = 'required';
            if (!trim($this->city))                         $this->errors['city']  = 'required';
        }

        if ($this->step === 1) {
            if (!$this->category) $this->errors['category'] = 'pick one';
        }

        if ($this->step === 2) {
            if (!trim($this->ecName))                           $this->errors['ecName']     = 'required';
            if (!preg_match('/^[0-9+\-\s]{8,}$/', $this->ecPhone)) $this->errors['ecPhone']    = 'invalid';
            if (!trim($this->ecRelation))                       $this->errors['ecRelation'] = 'required';
        }

        if ($this->step === 3) {
            if (!$this->payFile) $this->errors['payFile'] = 'upload required';
            if (!$this->agree)   $this->errors['agree']   = 'must agree';
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
        $event = Event::where('slug', $this->eventSlug)->firstOrFail();

        $proofPath = null;
        if ($this->payFile) {
            $proofPath = $this->payFile->store('proofs', 'public');
        }

        $this->entryCode = 'IB26-' . strtoupper(Str::random(5));

        Registration::create([
            'entry_code'     => $this->entryCode,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'dob'            => $this->dob,
            'city'           => $this->city,
            'stance'         => $this->stance,
            'event_id'       => $event->id,
            'category'       => $this->category,
            'experience'     => $this->experience,
            'ec_name'        => $this->ecName,
            'ec_phone'       => $this->ecPhone,
            'ec_relation'    => $this->ecRelation,
            'payment_method' => $this->payMethod,
            'payment_proof'  => $proofPath,
            'payment_status' => 'PENDING',
            'status'         => 'PENDING',
        ]);

        $event->increment('filled');

        $this->done = true;
        $this->step = 4;
    }

    public function render()
    {
        $events = Event::orderBy('date')->get();
        $currentEvent = Event::where('slug', $this->eventSlug)->first();

        return view('livewire.registration-form', compact('events', 'currentEvent'));
    }
}
