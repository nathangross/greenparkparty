<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartyUpdateResource\Pages;
use App\Models\PartyUpdate;
use App\Services\MailchimpUpdateCampaignService;
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
                Forms\Components\Radio::make('publish_target')
                    ->label('Publish to')
                    ->options([
                        PartyUpdate::PUBLISH_TARGET_HOMEPAGE => 'Homepage',
                        PartyUpdate::PUBLISH_TARGET_EMAIL => 'Email',
                        PartyUpdate::PUBLISH_TARGET_BOTH => 'Homepage and email',
                    ])
                    ->descriptions([
                        PartyUpdate::PUBLISH_TARGET_HOMEPAGE => 'Show this update on the public homepage only.',
                        PartyUpdate::PUBLISH_TARGET_EMAIL => 'Use this update for Mailchimp only.',
                        PartyUpdate::PUBLISH_TARGET_BOTH => 'Show it publicly and make it available for Mailchimp.',
                    ])
                    ->default(PartyUpdate::PUBLISH_TARGET_HOMEPAGE)
                    ->required()
                    ->inline(false)
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\Select::make('mailchimp_list_id')
                    ->label('Mailchimp audience')
                    ->options(fn (): array => app(MailchimpUpdateCampaignService::class)->mailchimpLists())
                    ->default(fn (): ?string => config('newsletter.lists.subscribers.id'))
                    ->helperText('Used when publishing this update to email. Leave as the configured default unless you need a different Mailchimp audience.')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set): mixed => $set('mailchimp_segment_id', null))
                    ->visible(fn (Forms\Get $get): bool => in_array($get('publish_target'), [
                        PartyUpdate::PUBLISH_TARGET_EMAIL,
                        PartyUpdate::PUBLISH_TARGET_BOTH,
                    ], true))
                    ->columnSpanFull(),
                Forms\Components\Select::make('mailchimp_segment_id')
                    ->label('Mailchimp segment/tag')
                    ->options(fn (Forms\Get $get): array => app(MailchimpUpdateCampaignService::class)->mailchimpSegments($get('mailchimp_list_id')))
                    ->helperText('Optional. Leave blank to send to the whole selected audience.')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Forms\Get $get): bool => in_array($get('publish_target'), [
                        PartyUpdate::PUBLISH_TARGET_EMAIL,
                        PartyUpdate::PUBLISH_TARGET_BOTH,
                    ], true))
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_published')
                    ->label('Published')
                    ->helperText('For homepage updates, this controls public visibility and scheduling. Email-only updates can remain unpublished until you send them.')
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
                Tables\Columns\TextColumn::make('publish_target')
                    ->label('Publish to')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PartyUpdate::PUBLISH_TARGET_HOMEPAGE => 'Homepage',
                        PartyUpdate::PUBLISH_TARGET_EMAIL => 'Email',
                        PartyUpdate::PUBLISH_TARGET_BOTH => 'Both',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        PartyUpdate::PUBLISH_TARGET_HOMEPAGE => 'success',
                        PartyUpdate::PUBLISH_TARGET_EMAIL => 'warning',
                        PartyUpdate::PUBLISH_TARGET_BOTH => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publish date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mailchimp_list_id')
                    ->label('Mailchimp Audience')
                    ->placeholder('Default')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mailchimp_segment_id')
                    ->label('Mailchimp Segment')
                    ->placeholder('Whole audience')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mailchimp_campaign_id')
                    ->label('Mailchimp Draft')
                    ->placeholder('Not created')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mailchimp_sent_at')
                    ->label('Mailchimp Sent')
                    ->dateTime()
                    ->placeholder('Not sent')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),
                Tables\Filters\SelectFilter::make('publish_target')
                    ->label('Publish to')
                    ->options([
                        PartyUpdate::PUBLISH_TARGET_HOMEPAGE => 'Homepage',
                        PartyUpdate::PUBLISH_TARGET_EMAIL => 'Email',
                        PartyUpdate::PUBLISH_TARGET_BOTH => 'Homepage and email',
                    ]),
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
