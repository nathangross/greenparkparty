<?php

use App\Models\Party;
use App\Mail\TestEmail;
use App\Mail\RSVPUpdateMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

View::composer('*', function ($view) {
    $party = Party::where('is_active', true)->first();
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

Route::get('/send-test-email', function () {
    $details = [
        'message' => 'This is a test email to check Mailgun configuration.'
    ];

    // Replace with your email address for testing
    Mail::to('nathan@bldg13.com')->send(new TestEmail($details));

    return 'Test email sent!';
});

require __DIR__ . '/auth.php';
