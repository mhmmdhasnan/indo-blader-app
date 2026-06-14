<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\JudgeScore;
use App\Models\Rider;
use App\Models\Registration;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Admin — Indo Blader')]
class Dashboard extends Component
{
    public string $view = 'overview';

    // Judging state
    public float $judgeExec       = 9.2;
    public float $judgeStyle      = 8.9;
    public float $judgeCreativity = 9.4;
    public float $judgeDiff       = 9.5;
    public bool  $scoreSubmitted  = false;

    public function submitScore(): void
    {
        $event  = Event::where('slug', 'nationals')->first();
        $rider  = Rider::where('slug', 'dimas')->first();

        if ($event && $rider) {
            $total = (($this->judgeExec + $this->judgeStyle + $this->judgeCreativity + $this->judgeDiff) / 4) * 10;

            JudgeScore::updateOrCreate(
                ['event_id' => $event->id, 'rider_id' => $rider->id, 'run_number' => 2],
                [
                    'execution'  => $this->judgeExec,
                    'style'      => $this->judgeStyle,
                    'creativity' => $this->judgeCreativity,
                    'difficulty' => $this->judgeDiff,
                    'total'      => round($total, 1),
                    'status'     => 'DONE',
                ]
            );
        }

        $this->scoreSubmitted = true;
    }

    public function approveRegistration(int $id): void
    {
        Registration::findOrFail($id)->update(['status' => 'APPROVED']);
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

    public function render()
    {
        $registrations = Registration::with('event')->latest()->get();
        $events        = Event::orderBy('date')->get();
        $riders        = Rider::orderByDesc('points')->get();
        $revenue       = $registrations->count() * 350000;

        $currentRider = Rider::where('slug', 'dimas')->first();

        return view('livewire.admin.dashboard', compact(
            'registrations', 'events', 'riders', 'revenue', 'currentRider'
        ));
    }
}
