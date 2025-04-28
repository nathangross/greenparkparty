<?php

use App\Models\Rsvp;
use App\Models\User;
use App\Models\Party;
use Livewire\Volt\Volt;
use Tests\Feature\Traits\WithViewComposer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);
uses(WithViewComposer::class);

test('true is true', function () {
    expect(true)->toBeTrue();
});

test('a user can submit an RSVP form', function () {
    // Create a party to ensure the party_id exists
    $party = Party::factory()->create(['is_active' => true]);
    $this->setUpViewComposer($party);

    Volt::test('rsvp-form')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('street', '1234 Main St')
        ->set('email', 'john@example.com')
        ->set('showAttending', true)
        ->set('attending_count', 2)
        ->set('phone', '123-456-7890')
        ->set('volunteer', true)
        ->set('message', 'I am a guest of honor.')
        ->set('receive_email_updates', true)
        ->set('receive_sms_updates', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('first_name', 'John')
        ->where('email', 'john@example.com')
        ->exists())->toBeTrue();

    expect(Rsvp::where('attending_count', 2)
        ->where('volunteer', 1)
        ->where('message', 'I am a guest of honor.')
        ->where('receive_email_updates', 1)
        ->where('receive_sms_updates', 0)
        ->exists())->toBeTrue();
});

test('validation errors are shown for invalid input', function () {
    $party = Party::factory()->create(['is_active' => true]);
    $this->setUpViewComposer($party);

    Volt::test('rsvp-form')
        ->set('first_name', '') // Required field
        ->set('email', 'not-an-email') // Invalid email
        ->set('receive_email_updates', true) // Email is required when receive_email_updates is true
        ->set('receive_sms_updates', true) // Phone is required when receive_sms_updates is true
        ->call('save')
        ->assertHasErrors([
            'first_name' => 'required',
            'email' => 'email',
            'phone' => 'required_if'
        ]);
});

test('optional fields are correctly handled', function () {
    $party = Party::factory()->create(['is_active' => true]);
    $this->setUpViewComposer($party);

    Volt::test('rsvp-form')
        ->set('first_name', 'Jane')
        ->set('last_name', null) // Optional field
        ->set('email', null) // Optional field when receive_email_updates is false
        ->set('showAttending', true)
        ->set('attending_count', 1)
        ->set('phone', null) // Optional field when receive_sms_updates is false
        ->set('street', null) // Optional field
        ->set('volunteer', false)
        ->set('message', null) // Optional field
        ->set('receive_email_updates', false) // Make sure email is optional
        ->set('receive_sms_updates', false) // Make sure phone is optional
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'first_name' => 'Jane',
        'email' => null,
        'phone' => null,
        'street' => null,
    ]);

    $this->assertDatabaseHas('rsvps', [
        'user_id' => User::where('first_name', 'Jane')->first()->id,
        'attending_count' => 1,
        'volunteer' => 0,
        'message' => null,
        'receive_email_updates' => 0,
        'receive_sms_updates' => 0,
    ]);
});

test('user can indicate they are not attending', function () {
    $party = Party::factory()->create(['is_active' => true]);
    $this->setUpViewComposer($party);

    Volt::test('rsvp-form')
        ->set('first_name', 'Bob')
        ->set('last_name', 'Smith')
        ->set('showAttending', false)
        ->set('attending_count', 0)
        ->set('volunteer', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'first_name' => 'Bob',
        'last_name' => 'Smith',
    ]);

    $this->assertDatabaseHas('rsvps', [
        'user_id' => User::where('first_name', 'Bob')->first()->id,
        'attending_count' => 0,
        'volunteer' => 0,
    ]);
});

test('rsvp cannot be submitted when there is no active party', function () {
    // Create a party but set it as inactive
    $party = Party::factory()->create(['is_active' => false]);
    $this->setUpViewComposer($party);

    Volt::test('rsvp-form')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('showAttending', true)
        ->set('attending_count', 1)
        ->call('save')
        ->assertDispatched('flash-error', 'RSVPs are not currently open.');

    // Verify no RSVP was created
    expect(Rsvp::count())->toBe(0);
});

test('rsvp cannot be submitted when there are no parties', function () {
    // Don't create any parties
    $this->setUpViewComposer(null);

    Volt::test('rsvp-form')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('showAttending', true)
        ->set('attending_count', 1)
        ->call('save')
        ->assertDispatched('flash-error', 'RSVPs are not currently open.');

    // Verify no RSVP was created
    expect(Rsvp::count())->toBe(0);
});

test('success message includes attending count when user RSVPs', function () {
    $party = Party::factory()->create(['is_active' => true]);
    $this->setUpViewComposer($party);

    Volt::test('rsvp-form')
        ->set('first_name', 'Alice')
        ->set('last_name', 'Johnson')
        ->set('showAttending', true)
        ->set('attending_count', 3)
        ->call('save')
        ->assertDispatched('flash-message')
        ->assertSee("Thanks Alice, we have you down for 3. We'll see you there!");

    expect(Rsvp::where('attending_count', 3)->exists())->toBeTrue();
});

test('success message is correct when user RSVPs as not attending', function () {
    $party = Party::factory()->create(['is_active' => true]);
    $this->setUpViewComposer($party);

    Volt::test('rsvp-form')
        ->set('first_name', 'Alice')
        ->set('showAttending', false)
        ->set('attending_count', 0)
        ->call('save')
        ->assertDispatched('flash-message')
        ->assertSee('Thanks for letting us know Alice. Hope to see you next year!');

    expect(Rsvp::where('attending_count', 0)->exists())->toBeTrue();
});

test('existing user is updated when RSVPing again', function () {
    $party = Party::factory()->create(['is_active' => true]);
    $this->setUpViewComposer($party);

    // First RSVP
    Volt::test('rsvp-form')
        ->set('first_name', 'John')
        ->set('last_name', 'Smith')
        ->set('email', 'john@example.com')
        ->set('phone', '123-456-7890')
        ->set('showAttending', true)
        ->set('attending_count', 2)
        ->call('save');

    // Second RSVP with updated information
    Volt::test('rsvp-form')
        ->set('first_name', 'Johnny')  // Changed name
        ->set('last_name', 'Smith')
        ->set('email', 'john@example.com')  // Same email
        ->set('phone', '098-765-4321')  // Changed phone
        ->set('showAttending', true)
        ->set('attending_count', 1)  // Changed count
        ->call('save');

    // Should only have one user
    expect(User::where('email', 'john@example.com')->count())->toBe(1);
    
    // User should have updated information
    $user = User::where('email', 'john@example.com')->first();
    expect($user->first_name)->toBe('Johnny');
    expect($user->phone)->toBe('098-765-4321');

    // Should have two RSVPs
    expect(Rsvp::where('user_id', $user->id)->count())->toBe(2);
});

test('attending count must be valid when attending', function () {
    $party = Party::factory()->create(['is_active' => true]);
    $this->setUpViewComposer($party);

    Volt::test('rsvp-form')
        ->set('first_name', 'John')
        ->set('showAttending', true)
        ->set('attending_count', -1)  // Invalid negative number
        ->call('save')
        ->assertHasErrors(['attending_count']);

    Volt::test('rsvp-form')
        ->set('first_name', 'John')
        ->set('showAttending', true)
        ->set('attending_count', 'not-a-number')  // Invalid non-numeric
        ->call('save')
        ->assertHasErrors(['attending_count']);

    Volt::test('rsvp-form')
        ->set('first_name', 'John')
        ->set('showAttending', true)
        ->set('attending_count', null)  // Required when attending
        ->call('save')
        ->assertHasErrors(['attending_count']);
});
