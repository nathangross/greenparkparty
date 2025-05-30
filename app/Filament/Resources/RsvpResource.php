<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Rsvp;
use App\Models\User;
use App\Models\Party;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\RsvpResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\RsvpResource\RelationManagers;
use Filament\Tables\Columns\BooleanColumn;
use Livewire\Attributes\On;

class RsvpResource extends Resource
{
    protected static ?string $model = Rsvp::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public ?int $selectedPartyId = null;

    public function mount(): void
    {
        $this->selectedPartyId = Party::where('is_active', true)->first()?->id 
            ?? Party::latest('primary_date_start')->first()?->id;
    }

    #[On('party-selected')]
    public function updateSelectedParty($partyId): void
    {
        $this->selectedPartyId = $partyId;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('party_id')
                    ->relationship('party', 'title')
                    ->required(),

                Forms\Components\TextInput::make('user.first_name')
                    ->label('Neighbor')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->user->first_name . ' ' . $record->user->last_name;
                    }),
                Forms\Components\TextInput::make('attending_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('volunteer')
                    ->required(),
                Forms\Components\Textarea::make('message_text')
                    ->label('Message')
                    ->columnSpanFull()
                    ->rows(4)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->when(
                Party::where('is_active', true)->exists(),
                fn ($q) => $q->whereHas('party', fn ($q) => $q->where('is_active', true))
            ))
            ->columns([
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('party.title')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.first_name')
                    ->label('Full Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        return $record->user->first_name . ' ' . $record->user->last_name;
                    }),
                Tables\Columns\TextColumn::make('attending_count')
                    ->label('Attending')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('volunteer')
                    ->boolean(),
                Tables\Columns\TextColumn::make('message_text')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('has_message')
                    ->label('Message')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->message_text))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('receive_email_updates')
                    ->label('Can Email')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->receive_email_updates)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('receive_sms_updates')
                    ->label('Can Text')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->receive_sms_updates)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('party')
                    ->relationship('party', 'title')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRsvps::route('/'),
            'create' => Pages\CreateRsvp::route('/create'),
            'view' => Pages\ViewRsvp::route('/{record}'),
            'edit' => Pages\EditRsvp::route('/{record}/edit'),
        ];
    }
}
