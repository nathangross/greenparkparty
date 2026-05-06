<?php

namespace App\Filament\Resources\PartyUpdateResource\Pages;

use App\Filament\Resources\PartyUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartyUpdates extends ListRecords
{
    protected static string $resource = PartyUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
