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

Route::get('/test-mailgun', function () {
    try {
        Mail::raw('This is a test email from Mailgun', function($message) {
            $message->to('nathan@bldg13.com')
                   ->subject('Mailgun Test');
        });
        
        return 'Test email sent successfully!';
    } catch (\Exception $e) {
        return 'Error sending email: ' . $e->getMessage();
    }
});

Route::get('/preview-rsvp-email', function () {
    if (!app()->environment('local', 'development')) {
        abort(404);
    }
    
    // Get the active party from the database
    $party = Party::where('is_active', true)->first();
    
    $user = new \App\Models\User([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com'
    ]);
    
    $rsvp = new \App\Models\Rsvp([
        'attending_count' => 3,
        'volunteer' => true,
        'message' => 'Test message',
        'receive_email_updates' => true,
        'receive_sms_updates' => false,
    ]);
    
    // Set up relationships without saving
    $rsvp->setRelation('party', $party);
    $rsvp->setRelation('user', $user);

    $notification = new \App\Notifications\RsvpConfirmation($rsvp);
    return $notification->toMail($user);
});

require __DIR__ . '/auth.php';
