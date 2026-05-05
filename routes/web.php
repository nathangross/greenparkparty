<?php

use App\Models\Party;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

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

if (app()->environment(['local', 'development'])) {
    Route::get('/test-mailgun', function () {
        try {
            Mail::raw('This is a test email from Mailgun', function ($message) {
                $message->to('nathan@bldg13.com')
                    ->subject('Mailgun Test');
            });

            return 'Test email sent successfully!';
        } catch (\Throwable $exception) {
            report($exception);

            return response('Unable to send test email.', 500);
        }
    });

    Route::get('/preview-rsvp-email', function () {
        $party = Party::where('is_active', true)->first();

        $user = new \App\Models\User([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $rsvp = new \App\Models\Rsvp([
            'attending_count' => 3,
            'volunteer' => true,
            'message_text' => 'Test message',
            'receive_email_updates' => true,
            'receive_sms_updates' => false,
        ]);

        $rsvp->setRelation('party', $party);
        $rsvp->setRelation('user', $user);

        $notification = new \App\Notifications\RsvpConfirmation($rsvp);

        return $notification->toMail($user);
    });
}

require __DIR__ . '/auth.php';
