<?php

use App\Models\Party;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $party = Party::where('is_active', true)->first()
        ?? Party::latest('primary_date_start')->first();

    $publicRsvps = $party
        ? $party->rsvps()
            ->with('user')
            ->where('show_on_homepage', true)
            ->latest()
            ->get()
        : collect();

    $privateAttendingCount = $party
        ? $party->rsvps()
            ->where('show_on_homepage', false)
            ->sum('attending_count')
        : 0;

    $expectedAttendeeCount = $party
        ? $party->rsvps()->sum('attending_count')
        : 0;

    $lastYearParty = $party
        ? Party::where('primary_date_start', '<', $party->primary_date_start)
            ->latest('primary_date_start')
            ->first()
        : null;

    $lastYearAttendeeCount = $lastYearParty
        ? $lastYearParty->rsvps()->sum('attending_count')
        : null;

    return view('welcome', [
        'party' => $party,
        'publicRsvps' => $publicRsvps,
        'privateAttendingCount' => $privateAttendingCount,
        'expectedAttendeeCount' => $expectedAttendeeCount,
        'lastYearParty' => $lastYearParty,
        'lastYearAttendeeCount' => $lastYearAttendeeCount,
    ]);
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
