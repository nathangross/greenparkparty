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

        $deadline = $party->getRsvpDeadline();

        if ($deadline && now()->isAfter($deadline->endOfDay())) {
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
