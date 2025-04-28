<?php

namespace App\Filament\Widgets;

use App\Models\Rsvp;
use App\Models\Party;
use App\Filament\Traits\HasOrganizerToggle;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RsvpYearComparison extends BaseWidget
{
    use HasOrganizerToggle;

    protected static ?string $pollingInterval = '10s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $activeParty = Party::where('is_active', true)->first();
        
        if (!$activeParty) {
            return [
                Stat::make('No Active Party', 'Please select an active party'),
            ];
        }

        // Get previous party in same month/day range
        $previousParty = Party::where('id', '!=', $activeParty->id)
            ->where('primary_date_start', '<', $activeParty->primary_date_start)
            ->orderBy('primary_date_start', 'desc')
            ->first();

        $description = $this->includeOrganizers ? '' : ' (Excluding Organizers)';

        if (!$previousParty) {
            return [
                Stat::make('Total Attending', $this->getPartyAttendance($activeParty))
                    ->description($activeParty->title . $description)
                    ->color('primary'),
                Stat::make('Total RSVPs', $this->getPartyRsvpCount($activeParty))
                    ->description('Total Responses' . $description)
                    ->color('primary'),
                Stat::make('Average Group Size', $this->calculateAverageGroupSize($activeParty))
                    ->description('People per RSVP' . $description)
                    ->color('primary'),
                Stat::make('Total Volunteers', $this->getPartyVolunteers($activeParty))
                    ->description($activeParty->title)
                    ->color('success'),
            ];
        }

        $currentAttending = $this->getPartyAttendance($activeParty);
        $previousAttending = $this->getPartyAttendance($previousParty);
        $attendingDiff = $this->calculatePercentageDiff($currentAttending, $previousAttending);

        $currentRsvps = $this->getPartyRsvpCount($activeParty);
        $previousRsvps = $this->getPartyRsvpCount($previousParty);
        $rsvpsDiff = $this->calculatePercentageDiff($currentRsvps, $previousRsvps);

        $currentAvgSize = $this->calculateAverageGroupSize($activeParty);
        $previousAvgSize = $this->calculateAverageGroupSize($previousParty);
        $avgSizeDiff = $this->calculatePercentageDiff($currentAvgSize, $previousAvgSize);

        $currentVolunteers = $this->getPartyVolunteers($activeParty);
        $previousVolunteers = $this->getPartyVolunteers($previousParty);
        $volunteersDiff = $this->calculatePercentageDiff($currentVolunteers, $previousVolunteers);

        return [
            Stat::make('Total Attending', $currentAttending)
                ->description("vs {$previousAttending} last year ({$attendingDiff}%){$description}")
                ->descriptionIcon($attendingDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($attendingDiff >= 0 ? 'success' : 'danger'),

            Stat::make('Total RSVPs', $currentRsvps)
                ->description("vs {$previousRsvps} last year ({$rsvpsDiff}%){$description}")
                ->descriptionIcon($rsvpsDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($rsvpsDiff >= 0 ? 'success' : 'danger'),

            Stat::make('Average Group Size', number_format($currentAvgSize, 1))
                ->description("vs " . number_format($previousAvgSize, 1) . " last year ({$avgSizeDiff}%){$description}")
                ->descriptionIcon($avgSizeDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($avgSizeDiff >= 0 ? 'success' : 'danger'),

            Stat::make('Total Volunteers', $currentVolunteers)
                ->description("vs {$previousVolunteers} last year ({$volunteersDiff}%)")
                ->descriptionIcon($volunteersDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($volunteersDiff >= 0 ? 'success' : 'danger'),
        ];
    }

    protected function calculatePercentageDiff($current, $previous): string
    {
        if ($previous == 0) return '∞';
        $diff = (($current - $previous) / $previous) * 100;
        return number_format($diff, 1);
    }

    protected function getPartyAttendance(Party $party): int
    {
        $query = Rsvp::where('party_id', $party->id);

        if (!$this->includeOrganizers) {
            $query->whereHas('user', function ($query) {
                $query->where('is_organizer', false);
            });
        }

        return $query->sum('attending_count');
    }

    protected function getPartyRsvpCount(Party $party): int
    {
        $query = Rsvp::where('party_id', $party->id);

        if (!$this->includeOrganizers) {
            $query->whereHas('user', function ($query) {
                $query->where('is_organizer', false);
            });
        }

        return $query->count();
    }

    protected function calculateAverageGroupSize(Party $party): float
    {
        $query = Rsvp::where('party_id', $party->id);

        if (!$this->includeOrganizers) {
            $query->whereHas('user', function ($query) {
                $query->where('is_organizer', false);
            });
        }
            
        $count = $query->count();
        return $count > 0 ? $query->sum('attending_count') / $count : 0;
    }

    protected function getPartyVolunteers(Party $party): int
    {
        return Rsvp::where('party_id', $party->id)->sum('volunteer');
    }
} 