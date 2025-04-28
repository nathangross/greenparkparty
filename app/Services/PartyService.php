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
        return $this->getActiveParty() !== null;
    }

    public function getCurrentPartyYear(): ?string
    {
        $party = $this->getActiveParty();
        return $party ? $party->primary_date_start->format('Y') : null;
    }
} 