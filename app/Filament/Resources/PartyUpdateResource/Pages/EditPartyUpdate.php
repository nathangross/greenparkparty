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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['is_published'] ?? false) {
            $data['published_at'] ??= now();
        } else {
            $data['published_at'] = null;
        }

        return $data;
    }
}
