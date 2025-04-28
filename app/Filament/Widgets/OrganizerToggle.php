<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class OrganizerToggle extends Widget
{
    public bool $isEnabled = false;

    protected static string $view = 'filament.widgets.organizer-toggle';

    protected int | string | array $columnSpan = 'full';

    public function toggle(): void
    {
        $this->isEnabled = !$this->isEnabled;
        $this->dispatch('organizers-toggled', isEnabled: $this->isEnabled);
    }
} 