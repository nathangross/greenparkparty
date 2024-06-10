<?php

namespace App\Filament\Widgets;

use App\Models\Rsvp;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class RsvpCount extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Attending', Rsvp::sum('attending_count')),
            Stat::make('Total RSVPs', Rsvp::count()),
        ];
    }
}
