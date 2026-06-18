<?php

namespace App\Livewire\Rider;

use App\Models\BattleSubmission;
use App\Models\BracketMatch;
use App\Models\Notification;
use App\Models\QualificationMatch;
use App\Models\Registration;
use App\Models\Rider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Dashboard — Indo Blader')]
class Dashboard extends Component
{
    use WithFileUploads;

    public string $view = 'overview';

    // Submit video
    public string $selectedMatchType = 'QUALIFICATION';
    public int    $selectedMatchId   = 0;
    public string $videoUrl          = '';
    public bool   $uploadSuccess     = false;
    public string $uploadError       = '';

    // Profile photo
    public $avatarFile        = null;
    public bool $avatarSaved  = false;
    public string $avatarError = '';

    public function uploadAvatar(): void
    {
        $this->avatarSaved = false;
        $this->avatarError = '';

        $this->validate([
            'avatarFile' => 'required|image|max:2048',
        ], [
            'avatarFile.required' => 'Pilih foto terlebih dahulu.',
            'avatarFile.image'    => 'File harus berupa gambar (jpg, png, webp).',
            'avatarFile.max'      => 'Ukuran foto maksimal 2MB.',
        ]);

        $rider = Rider::where('user_id', auth()->id())->first();
        if (!$rider) {
            $this->avatarError = 'Profil rider belum dibuat.';
            return;
        }

        $path = $this->avatarFile->store('avatars', 'public');
        $rider->update(['avatar' => $path]);

        $this->avatarFile  = null;
        $this->avatarSaved = true;
    }

    public function markNotificationRead(int $id): void
    {
        $reg = $this->getUserRegistrationIds();
        Notification::whereIn('notifiable_id', $reg)->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        $reg = $this->getUserRegistrationIds();
        Notification::whereIn('notifiable_id', $reg)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function submitVideo(): void
    {
        $this->uploadError   = '';
        $this->uploadSuccess = false;

        $this->validate([
            'selectedMatchId' => 'required|integer|min:1',
            'videoUrl'        => [
                'required',
                'url',
                'regex:#^https://www\.instagram\.com/(p|reel)/[A-Za-z0-9_-]+/?#',
            ],
        ], [
            'selectedMatchId.min' => 'Pilih match terlebih dahulu.',
            'videoUrl.required'   => 'Masukkan link Instagram.',
            'videoUrl.regex'      => 'Format link harus: https://www.instagram.com/p/KODE/ atau /reel/KODE/',
        ]);

        $user          = auth()->user();
        $registrations = Registration::where('user_id', $user->id)->pluck('id');

        if ($this->selectedMatchType === 'QUALIFICATION') {
            $match = QualificationMatch::findOrFail($this->selectedMatchId);
            $regId = $registrations->intersect(
                collect([$match->rider_a_registration_id, $match->rider_b_registration_id])
            )->first();
        } else {
            $match = BracketMatch::findOrFail($this->selectedMatchId);
            $regId = $registrations->intersect(
                collect([$match->rider_a_registration_id, $match->rider_b_registration_id])
            )->first();
        }

        if (!$regId) {
            $this->uploadError = 'Kamu tidak terdaftar dalam match ini.';
            return;
        }

        $existing = BattleSubmission::where('match_type', $this->selectedMatchType)
            ->where('match_id', $this->selectedMatchId)
            ->where('registration_id', $regId)
            ->whereNotIn('status', ['REJECTED'])
            ->first();

        if ($existing && $existing->status !== 'NEED_REUPLOAD') {
            $this->uploadError = 'Kamu sudah submit video untuk match ini.';
            return;
        }

        $cleanUrl = rtrim($this->videoUrl, '/') . '/';

        if ($existing) {
            $existing->update(['video_path' => $cleanUrl, 'status' => 'PENDING', 'judge_feedback' => null]);
        } else {
            BattleSubmission::create([
                'match_type'      => $this->selectedMatchType,
                'match_id'        => $this->selectedMatchId,
                'registration_id' => $regId,
                'video_path'      => $cleanUrl,
                'status'          => 'PENDING',
            ]);
        }

        $this->videoUrl        = '';
        $this->selectedMatchId = 0;
        $this->uploadSuccess   = true;
    }

    private function getUserRegistrationIds(): array
    {
        return Registration::where('user_id', auth()->id())->pluck('id')->toArray();
    }

    public function render()
    {
        $user          = auth()->user();
        $registrations = Registration::where('user_id', $user->id)->with('event')->latest()->get();
        $regIds        = $registrations->pluck('id')->toArray();

        $unreadCount = Notification::whereIn('notifiable_id', $regIds)->whereNull('read_at')->count();
        $rider       = Rider::where('user_id', $user->id)->first();

        $data = compact('registrations', 'unreadCount', 'rider');

        if ($this->view === 'notifications') {
            $data['notifications'] = Notification::whereIn('notifiable_id', $regIds)
                ->orderByRaw('read_at IS NOT NULL')
                ->orderByDesc('created_at')
                ->get();
        }

        if ($this->view === 'upload') {
            $data['qualMatches'] = QualificationMatch::whereIn('rider_a_registration_id', $regIds)
                ->orWhereIn('rider_b_registration_id', $regIds)
                ->with(['qualificationRound.event', 'trick'])
                ->get();
            $data['bracketMatches'] = BracketMatch::whereIn('rider_a_registration_id', $regIds)
                ->orWhereIn('rider_b_registration_id', $regIds)
                ->with(['bracket.event', 'trick'])
                ->get();
            $data['submissions'] = BattleSubmission::whereIn('registration_id', $regIds)
                ->latest()
                ->get();

            // Trick for currently selected match
            $data['selectedTrick'] = null;
            if ($this->selectedMatchId) {
                if ($this->selectedMatchType === 'QUALIFICATION') {
                    $data['selectedTrick'] = QualificationMatch::with('trick')->find($this->selectedMatchId)?->trick;
                } else {
                    $data['selectedTrick'] = BracketMatch::with('trick')->find($this->selectedMatchId)?->trick;
                }
            }
        }

        return view('livewire.rider.dashboard', $data);
    }
}
