<?php

namespace App\Filament\Widgets;

use App\Models\Party;
use Filament\Widgets\Widget;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;

class ActivePartySelector extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.active-party-selector';

    public ?Party $party = null;
    
    public array $data = [];

    public function mount(): void
    {
        $this->party = Party::where('is_active', true)->first();
        
        $this->form->fill([
            'party_id' => $this->party?->id,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('party_id')
                ->label('Active Party')
                ->options(Party::pluck('title', 'id'))
                ->default(fn () => Party::where('is_active', true)->first()?->id)
                ->live()
                ->afterStateUpdated(function ($state) {
                    // Deactivate all parties
                    Party::query()->update(['is_active' => false]);
                    // Activate selected party
                    if ($state) {
                        Party::find($state)->update(['is_active' => true]);
                        $this->party = Party::find($state);
                    }
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