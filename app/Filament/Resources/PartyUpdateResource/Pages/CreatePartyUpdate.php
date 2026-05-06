<?php

namespace App\Filament\Resources\PartyUpdateResource\Pages;

use App\Filament\Resources\PartyUpdateResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePartyUpdate extends CreateRecord
{
    protected static string $resource = PartyUpdateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['is_published'] ?? false) {
            $data['published_at'] ??= now();
        } else {
            $data['published_at'] = null;
        }

        return $data;
    }
}
