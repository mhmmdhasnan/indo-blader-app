<?php

use App\Livewire\About;
use App\Livewire\Admin\Dashboard;
use App\Livewire\BracketPage;
use App\Livewire\EventDetail;
use App\Livewire\EventsList;
use App\Livewire\Gallery;
use App\Livewire\Home;
use App\Livewire\LiveScoring;
use App\Livewire\Rankings;
use App\Livewire\RegistrationForm;
use App\Livewire\RiderProfile;
use App\Livewire\RidersList;
use Illuminate\Support\Facades\Route;

Route::get('/',          Home::class)->name('home');
Route::get('/events',    EventsList::class)->name('events');
Route::get('/events/{slug}', EventDetail::class)->name('events.show');
Route::get('/rankings',  Rankings::class)->name('rankings');
Route::get('/riders',    RidersList::class)->name('riders');
Route::get('/riders/{slug}', RiderProfile::class)->name('riders.show');
Route::get('/live',      LiveScoring::class)->name('live');
Route::get('/bracket',   BracketPage::class)->name('bracket');
Route::get('/register',  RegistrationForm::class)->name('register');
Route::get('/gallery',   Gallery::class)->name('gallery');
Route::get('/about',     About::class)->name('about');
Route::get('/admin',     Dashboard::class)->name('admin');
