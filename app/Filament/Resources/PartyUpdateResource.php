<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartyUpdateResource\Pages;
use App\Models\PartyUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartyUpdateResource extends Resource
{
    protected static ?string $model = PartyUpdate::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Updates';

    protected static ?string $modelLabel = 'Update';

    protected static ?string $pluralModelLabel = 'Updates';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('party_id')
                    ->relationship('party', 'title')
                    ->label('Party')
                    ->helperText('Leave blank for a general update.')
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('body')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'bulletList',
                        'orderedList',
                        'link',
                        'undo',
                        'redo',
                    ])
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_published')
                    ->label('Published')
                    ->helperText('Turn this on when the update is ready to appear publicly.')
                    ->default(false)
                    ->live(),
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Publish date')
                    ->seconds(false)
                    ->visible(fn (Forms\Get $get): bool => (bool) $get('is_published'))
                    ->helperText('Leave blank to publish immediately when saved. Use a future date to schedule.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('party.title')
                    ->label('Party')
                    ->placeholder('General')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publish date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartyUpdates::route('/'),
            'create' => Pages\CreatePartyUpdate::route('/create'),
            'edit' => Pages\EditPartyUpdate::route('/{record}/edit'),
        ];
    }
}
