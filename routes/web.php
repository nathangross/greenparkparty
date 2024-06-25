<?php

use App\Models\Party;
use App\Mail\TestEmail;
use App\Mail\RSVPUpdateMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

View::composer('*', function ($view) {
    try {
        $party = Party::findOrFail(1);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        $party = null;
    }
    $view->with('party', $party);
});

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::view('claybourne-grille', 'menu')->name('menu');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
