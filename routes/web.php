<?php

use App\Livewire\About;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register as RegisterAccount;
use App\Livewire\BracketPage;
use App\Livewire\EventDetail;
use App\Livewire\EventsList;
use App\Livewire\Gallery;
use App\Livewire\Home;
use App\Livewire\Judge\Dashboard as JudgeDashboard;
use App\Livewire\LiveScoring;
use App\Livewire\NotificationInbox;
use App\Livewire\QualificationPage;
use App\Livewire\Rankings;
use App\Livewire\RegistrationForm;
use App\Livewire\Rider\Dashboard as RiderDashboard;
use App\Livewire\RiderProfile;
use App\Livewire\RidersList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Auth routes ─────────────────────────────────────────────────────────────
Route::get('/login',  Login::class)->name('login')->middleware('guest');
Route::get('/daftar', RegisterAccount::class)->name('register.account')->middleware('guest');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// ── Role-protected routes ────────────────────────────────────────────────────
Route::get('/admin', Dashboard::class)->name('admin')->middleware('role:admin');
Route::get('/judge', JudgeDashboard::class)->name('judge')->middleware('role:judge,head_judge');
Route::get('/rider', RiderDashboard::class)->name('rider.dashboard')->middleware('role:rider');

// ── Public routes ────────────────────────────────────────────────────────────
Route::get('/',          Home::class)->name('home');
Route::get('/events',    EventsList::class)->name('events');
Route::get('/events/{slug}', EventDetail::class)->name('events.show');
Route::get('/events/{slug}/qualification', QualificationPage::class)->name('events.qualification');
Route::get('/rankings',  Rankings::class)->name('rankings');
Route::get('/riders',    RidersList::class)->name('riders');
Route::get('/riders/{slug}', RiderProfile::class)->name('riders.show');
Route::get('/live',      LiveScoring::class)->name('live');
Route::get('/bracket/{slug?}', BracketPage::class)->name('bracket');
Route::get('/register',  RegistrationForm::class)->name('register');
Route::get('/gallery',   Gallery::class)->name('gallery');
Route::get('/about',     About::class)->name('about');
Route::get('/notifications/{entry_code}', NotificationInbox::class)->name('notifications');
