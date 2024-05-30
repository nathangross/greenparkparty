<?php

use App\Models\Rsvp;
use App\Models\User;
use App\Models\Party;
use Livewire\Livewire;
use App\Livewire\RsvpForm;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can submit an RSVP form', function () {
    // Create a party to ensure the party_id exists
    $party = Party::factory()->create();

    Livewire::test(RsvpForm::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('street', '1234 Main St')
        ->set('email', 'john@example.com')
        ->set('attending_count', 2)
        ->set('phone', '123-456-7890')
        ->set('volunteer', true)
        ->set('message', 'I am a guest of honor.')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('first_name', 'John')
        ->where('email', 'john@example.com')
        ->exists())->toBeTrue();

    expect(Rsvp::where('attending_count', 2)
        ->where('volunteer', 1)
        ->where('message', 'I am a guest of honor.')
        ->exists())->toBeTrue();
});

test('validation errors are shown for invalid input', function () {
    Livewire::test(RsvpForm::class)
        ->set('first_name', '') // Required field
        ->set('email', 'not-an-email') // Invalid email
        ->call('save')
        ->assertHasErrors(['first_name' => 'required', 'email' => 'email']);
});

test('optional fields are correctly handled', function () {
    $party = Party::factory()->create();

    Livewire::test(RsvpForm::class)
        ->set('first_name', 'Jane')
        ->set('last_name', null) // Optional field
        ->set('email', 'jane@example.com')
        ->set('attending_count', 1)
        ->set('phone', null) // Optional field
        ->set('street', null) // Optional field
        ->set('volunteer', false)
        ->set('message', null) // Optional field
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'first_name' => 'Jane',
        'email' => 'jane@example.com',
        'phone' => null,
        'street' => null,
    ]);

    $this->assertDatabaseHas('rsvps', [
        'user_id' => User::where('email', 'jane@example.com')->first()->id,
        'attending_count' => 1,
        'volunteer' => 0,
        'message' => null,
    ]);
});
