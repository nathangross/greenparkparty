<?php

namespace App\Filament\Resources\PartyUpdateResource\Pages;

use App\Filament\Resources\PartyUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartyUpdate extends EditRecord
{
    protected static string $resource = PartyUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
