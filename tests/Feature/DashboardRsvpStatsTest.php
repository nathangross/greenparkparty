<?php

use App\Filament\Widgets\RsvpYearComparison;
use App\Models\Party;
use App\Models\Rsvp;
use App\Models\User;
use Carbon\Carbon;

function dashboardStatsWidget(): RsvpYearComparison
{
    return new class extends RsvpYearComparison
    {
        public function statsForTest(): array
        {
            return $this->getStats();
        }
    };
}

test('dashboard stats use the latest active party', function () {
    $lastYear = Party::factory()->create([
        'title' => 'Second Annual - 2025',
        'is_active' => true,
        'primary_date_start' => Carbon::parse('2025-06-28 16:00:00'),
    ]);

    $currentYear = Party::factory()->create([
        'title' => 'Third Annual - 2026',
        'is_active' => true,
        'primary_date_start' => Carbon::parse('2026-06-27 16:00:00'),
    ]);

    Rsvp::factory()->create([
        'party_id' => $lastYear->id,
        'user_id' => User::factory(),
        'attending_count' => 20,
        'volunteer' => true,
    ]);

    Rsvp::factory()->create([
        'party_id' => $currentYear->id,
        'user_id' => User::factory(),
        'attending_count' => 3,
        'volunteer' => true,
    ]);

    $stats = dashboardStatsWidget()->statsForTest();

    expect($stats[0]->getLabel())->toBe('Expected This Year')
        ->and($stats[0]->getValue())->toBe(3)
        ->and($stats[1]->getLabel())->toBe('Households Responded')
        ->and($stats[1]->getValue())->toBe(1)
        ->and($stats[3]->getLabel())->toBe('Volunteers')
        ->and($stats[3]->getValue())->toBe(1);
});

test('dashboard stats exclude organizers by default', function () {
    $party = Party::factory()->create([
        'title' => 'Third Annual - 2026',
        'is_active' => true,
        'primary_date_start' => Carbon::parse('2026-06-27 16:00:00'),
    ]);

    Rsvp::factory()->create([
        'party_id' => $party->id,
        'user_id' => User::factory()->create(['is_organizer' => false]),
        'attending_count' => 3,
    ]);

    Rsvp::factory()->create([
        'party_id' => $party->id,
        'user_id' => User::factory()->create(['is_organizer' => true]),
        'attending_count' => 9,
    ]);

    $widget = dashboardStatsWidget();
    $stats = $widget->statsForTest();

    expect($stats[0]->getValue())->toBe(3);

    $widget->includeOrganizers = true;
    $stats = $widget->statsForTest();

    expect($stats[0]->getValue())->toBe(12);
});
