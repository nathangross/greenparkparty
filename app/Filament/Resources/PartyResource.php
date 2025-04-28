<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartyResource\Pages;
use App\Filament\Resources\PartyResource\RelationManagers;
use App\Models\Party;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\BooleanColumn;

class PartyResource extends Resource
{
    protected static ?string $model = Party::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('primary_date_start')
                    ->required(),
                Forms\Components\DateTimePicker::make('primary_date_end')
                    ->required(),
                Forms\Components\DateTimePicker::make('secondary_date_start'),
                Forms\Components\DateTimePicker::make('secondary_date_end'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\BooleanColumn::make('is_active')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('primary_date_start')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('primary_date_end')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('secondary_date_start')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('secondary_date_end')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListParties::route('/'),
            'create' => Pages\CreateParty::route('/create'),
            'view' => Pages\ViewParty::route('/{record}'),
            'edit' => Pages\EditParty::route('/{record}/edit'),
        ];
    }
}
