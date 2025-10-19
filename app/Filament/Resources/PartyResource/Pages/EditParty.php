<?php

namespace App\Filament\Resources\PartyResource\Pages;

use App\Filament\Resources\PartyResource;
use App\Models\Party;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParty extends EditRecord
{
    protected static string $resource = PartyResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this party is being set as active, deactivate all others
        if ($data['is_active'] ?? false) {
            Party::query()->update(['is_active' => false]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
