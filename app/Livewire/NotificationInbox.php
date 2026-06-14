<?php

namespace App\Livewire;

use App\Models\Notification;
use App\Models\Registration;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Notifications — Indo Blader')]
class NotificationInbox extends Component
{
    public string $entryCode;
    public Registration $registration;

    public function mount(string $entry_code): void
    {
        $this->registration = Registration::where('entry_code', $entry_code)->firstOrFail();
        $this->entryCode    = $entry_code;
    }

    public function markRead(int $id): void
    {
        Notification::where('id', $id)
            ->where('notifiable_id', $this->registration->id)
            ->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        Notification::where('notifiable_id', $this->registration->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        $notifications = Notification::where('notifiable_id', $this->registration->id)
            ->orderByRaw('read_at IS NOT NULL, created_at DESC')
            ->get();

        $unreadCount = $notifications->whereNull('read_at')->count();

        return view('livewire.notification-inbox', compact('notifications', 'unreadCount'));
    }
}
