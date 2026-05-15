<?php

namespace App\Filament\Widgets;

use App\Filament\Traits\HasOrganizerToggle;
use App\Models\Party;
use App\Models\Rsvp;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class RsvpYearComparison extends BaseWidget
{
    use HasOrganizerToggle;

    protected static ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $activeParty = Party::currentForDashboard();

        if (! $activeParty) {
            return [
                Stat::make('No Party', 'Create a party to see RSVP stats'),
            ];
        }

        $previousParty = Party::previousBefore($activeParty);

        $description = $this->includeOrganizers ? '' : ' (Excluding Organizers)';

        if (! $previousParty) {
            return [
                Stat::make('Expected This Year', $this->getPartyAttendance($activeParty))
                    ->description($activeParty->title.$description)
                    ->color('primary'),
                Stat::make('Households Responded', $this->getPartyRespondedCount($activeParty))
                    ->description('RSVP records'.$description)
                    ->color('primary'),
                Stat::make('Average Group Size', $this->calculateAverageGroupSize($activeParty))
                    ->description('People per RSVP'.$description)
                    ->color('primary'),
                Stat::make('Volunteers', $this->getPartyVolunteers($activeParty))
                    ->description($activeParty->title)
                    ->color('success'),
            ];
        }

        $currentAttending = $this->getPartyAttendance($activeParty);
        $previousAttending = $this->getPartyAttendance($previousParty);
        $attendingDiff = $this->calculatePercentageDiff($currentAttending, $previousAttending);

        $currentRsvps = $this->getPartyRespondedCount($activeParty);
        $previousRsvps = $this->getPartyRespondedCount($previousParty);
        $rsvpsDiff = $this->calculatePercentageDiff($currentRsvps, $previousRsvps);

        $currentAvgSize = $this->calculateAverageGroupSize($activeParty);
        $previousAvgSize = $this->calculateAverageGroupSize($previousParty);
        $avgSizeDiff = $this->calculatePercentageDiff($currentAvgSize, $previousAvgSize);

        $currentVolunteers = $this->getPartyVolunteers($activeParty);
        $previousVolunteers = $this->getPartyVolunteers($previousParty);
        $volunteersDiff = $this->calculatePercentageDiff($currentVolunteers, $previousVolunteers);

        return [
            Stat::make('Expected This Year', $currentAttending)
                ->description($this->comparisonDescription($previousAttending, $attendingDiff, $description))
                ->descriptionIcon($this->trendIcon($attendingDiff))
                ->color($this->trendColor($attendingDiff)),

            Stat::make('Households Responded', $currentRsvps)
                ->description($this->comparisonDescription($previousRsvps, $rsvpsDiff, $description))
                ->descriptionIcon($this->trendIcon($rsvpsDiff))
                ->color($this->trendColor($rsvpsDiff)),

            Stat::make('Average Group Size', number_format($currentAvgSize, 1))
                ->description($this->comparisonDescription(number_format($previousAvgSize, 1), $avgSizeDiff, $description))
                ->descriptionIcon($this->trendIcon($avgSizeDiff))
                ->color($this->trendColor($avgSizeDiff)),

            Stat::make('Volunteers', $currentVolunteers)
                ->description($this->comparisonDescription($previousVolunteers, $volunteersDiff, $description))
                ->descriptionIcon($this->trendIcon($volunteersDiff))
                ->color($this->trendColor($volunteersDiff)),
        ];
    }

    protected function calculatePercentageDiff(float|int $current, float|int $previous): ?string
    {
        if ($previous == 0) {
            return null;
        }

        $diff = (($current - $previous) / $previous) * 100;

        return number_format($diff, 1);
    }

    protected function comparisonDescription(string|int|float $previous, ?string $diff, string $suffix = ''): string
    {
        if ($diff === null) {
            return "vs {$previous} last year{$suffix}";
        }

        return "vs {$previous} last year ({$diff}%){$suffix}";
    }

    protected function trendIcon(?string $diff): ?string
    {
        if ($diff === null) {
            return null;
        }

        return (float) $diff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function trendColor(?string $diff): string
    {
        if ($diff === null) {
            return 'gray';
        }

        return (float) $diff >= 0 ? 'success' : 'danger';
    }

    protected function getPartyAttendance(Party $party): int
    {
        return $this->partyRsvps($party)->sum('attending_count');
    }

    protected function getPartyRespondedCount(Party $party): int
    {
        return $this->partyRsvps($party)->count();
    }

    protected function calculateAverageGroupSize(Party $party): float
    {
        $query = $this->partyRsvps($party)->where('attending_count', '>', 0);

        $count = $query->count();

        return $count > 0 ? $query->sum('attending_count') / $count : 0;
    }

    protected function getPartyVolunteers(Party $party): int
    {
        return $this->partyRsvps($party)
            ->where('attending_count', '>', 0)
            ->where('volunteer', true)
            ->count();
    }

    protected function partyRsvps(Party $party): Builder
    {
        return Rsvp::query()
            ->forParty($party)
            ->forDashboard($this->includeOrganizers);
    }
}
