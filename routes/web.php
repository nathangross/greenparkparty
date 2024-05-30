<?php

use App\Models\Party;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    try {
        $party = Party::findOrFail(1);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        $party = null;
    }

    return view('welcome', ['party' => $party]);
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
