<?php

namespace App\Filament\Widgets;

use App\Models\Rsvp;
use App\Models\Party;
use App\Filament\Traits\HasOrganizerToggle;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RsvpTrendChart extends ChartWidget
{
    use HasOrganizerToggle;

    protected static ?string $heading = 'RSVP Response Rate';

    protected static ?string $pollingInterval = '10s';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $activeParty = Party::where('is_active', true)->first();
        
        if (!$activeParty) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Get previous party
        $previousParty = Party::where('id', '!=', $activeParty->id)
            ->where('primary_date_start', '<', $activeParty->primary_date_start)
            ->orderBy('primary_date_start', 'desc')
            ->first();

        if (!$previousParty) {
            // Only show current party data if no previous party
            $currentData = $this->getPartyRsvpTrend($activeParty);
            
            return [
                'datasets' => [
                    [
                        'label' => $activeParty->title,
                        'data' => array_values($currentData),
                        'borderColor' => '#36A2EB',
                        'fill' => false,
                    ],
                ],
                'labels' => array_keys($currentData),
            ];
        }

        // Get data for both parties
        $currentData = $this->getPartyRsvpTrend($activeParty);
        $previousData = $this->getPartyRsvpTrend($previousParty);

        // Find the range of days to show
        $allDays = array_unique([...array_keys($currentData), ...array_keys($previousData)]);
        sort($allDays);

        return [
            'datasets' => [
                [
                    'label' => $activeParty->title,
                    'data' => array_map(fn($day) => $currentData[$day] ?? null, $allDays),
                    'borderColor' => '#36A2EB',
                    'fill' => false,
                ],
                [
                    'label' => $previousParty->title,
                    'data' => array_map(fn($day) => $previousData[$day] ?? null, $allDays),
                    'borderColor' => '#FF6384',
                    'fill' => false,
                ],
            ],
            'labels' => $allDays,
        ];
    }

    protected function getPartyRsvpTrend(Party $party): array
    {
        $query = Rsvp::where('party_id', $party->id);

        if (!$this->includeOrganizers) {
            $query->whereHas('user', function ($query) {
                $query->where('is_organizer', false);
            });
        }

        $rsvps = $query->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(attending_count) as total_attending')
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = [];
        $runningTotal = 0;
        $startDate = $rsvps->min('date');

        if (!$startDate) {
            return [];
        }

        foreach ($rsvps as $rsvp) {
            $daysDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($rsvp->date));
            $runningTotal += $rsvp->total_attending;
            $data["Day {$daysDiff}"] = $runningTotal;
        }

        return $data;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Days Since First RSVP',
                    ],
                ],
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Total RSVPs',
                    ],
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    public static function getSort(): int
    {
        return 3;
    }
} 