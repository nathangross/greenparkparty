<?php

namespace App\Services;

use App\Models\Party;

class PartyService
{
    public function getActiveParty(): ?Party
    {
        return Party::where('is_active', true)->first();
    }

    public function isAcceptingRsvps(): bool
    {
        $party = $this->getActiveParty();

        if (!$party) {
            return false;
        }

        // Close RSVPs the day after the party ends
        if ($party->primary_date_end && now()->isAfter($party->primary_date_end->endOfDay())) {
            return false;
        }

        return true;
    }

    public function getCurrentPartyYear(): ?string
    {
        $party = $this->getActiveParty();
        return $party ? $party->primary_date_start->format('Y') : null;
    }
} 