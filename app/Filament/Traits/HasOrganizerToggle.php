<?php

namespace App\Filament\Traits;

use Livewire\Attributes\On;

trait HasOrganizerToggle
{
    public bool $includeOrganizers = false;

    #[On('organizers-toggled')] 
    public function handleOrganizerToggle(bool $isEnabled): void
    {
        $this->includeOrganizers = $isEnabled;
    }
} 