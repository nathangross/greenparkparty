<?php

namespace App\Filament\Resources\RsvpResource\Pages;

use App\Filament\Resources\RsvpResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRsvp extends ViewRecord
{
    protected static string $resource = RsvpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
