<?php

namespace App\Filament\Widgets;

use App\Models\Party;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;

class ActivePartySelector extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.active-party-selector';

    public ?Party $party = null;

    public array $data = [];

    public function mount(): void
    {
        $this->party = Party::currentForDashboard();

        $this->form->fill([
            'party_id' => $this->party?->id,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('party_id')
                ->label('Active Party')
                ->options(fn () => Party::query()
                    ->orderByDesc('primary_date_start')
                    ->orderByDesc('id')
                    ->pluck('title', 'id'))
                ->default(fn () => Party::currentForDashboard()?->id)
                ->live()
                ->afterStateUpdated(function ($state) {
                    // Deactivate all parties
                    Party::query()->update(['is_active' => false]);
                    // Activate selected party
                    if ($state) {
                        Party::find($state)->update(['is_active' => true]);
                        $this->party = Party::find($state);
                    }

                    $this->dispatch('active-party-changed');
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema($this->getFormSchema());
    }
}
