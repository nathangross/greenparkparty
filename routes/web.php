<?php

use App\Models\Party;
use Illuminate\Support\Facades\Route;

$party = Party::find(1);

Route::view('/', 'welcome', ['party' => $party]);

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
