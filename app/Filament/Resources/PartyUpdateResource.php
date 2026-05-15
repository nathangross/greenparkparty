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
                Forms\Components\Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->schema([
                        Forms\Components\Group::make([
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
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                        Forms\Components\Section::make('Email')
                            ->description('Mailchimp delivery settings and status.')
                            ->schema([
                                Forms\Components\TextInput::make('email_subject')
                                    ->label('Subject')
                                    ->maxLength(255)
                                    ->placeholder(fn (Forms\Get $get): string => $get('title') ?: 'Uses the update title')
                                    ->helperText('Optional. Leave blank to use the update title.'),
                                Forms\Components\Select::make('mailchimp_list_id')
                                    ->label('Audience')
                                    ->options(fn (): array => app(MailchimpUpdateCampaignService::class)->mailchimpLists())
                                    ->default(fn (): ?string => config('newsletter.lists.subscribers.id'))
                                    ->helperText('Leave as the configured default unless you need a different audience.')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set): mixed => $set('mailchimp_segment_id', null)),
                                Forms\Components\Select::make('mailchimp_segment_id')
                                    ->label('Segment/tag')
                                    ->options(fn (Forms\Get $get): array => app(MailchimpUpdateCampaignService::class)->mailchimpSegments($get('mailchimp_list_id')))
                                    ->helperText('Optional. Leave blank to send to the whole selected audience.')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Placeholder::make('mailchimp_status_display')
                                    ->label('Mailchimp status')
                                    ->content(fn (?PartyUpdate $record): string => match ($record?->mailchimp_status) {
                                        'save' => 'Draft',
                                        'sent' => 'Sent',
                                        'sending' => 'Sending',
                                        'schedule' => 'Scheduled',
                                        null => 'Not created',
                                        default => $record->mailchimp_status,
                                    }),
                                Forms\Components\Placeholder::make('mailchimp_campaign_display')
                                    ->label('Campaign')
                                    ->content(fn (?PartyUpdate $record): string => $record?->mailchimp_campaign_id ?: 'Not created'),
                                Forms\Components\Placeholder::make('mailchimp_sent_display')
                                    ->label('Sent')
                                    ->content(fn (?PartyUpdate $record): string => $record?->mailchimp_sent_at?->format('M j, Y g:i A') ?: 'Not sent'),
                                Forms\Components\Placeholder::make('mailchimp_from_display')
                                    ->label('Sender')
                                    ->content(fn (): string => (string) config('mail.from.address')),
                                Forms\Components\Placeholder::make('mailchimp_error_display')
                                    ->label('Last Mailchimp error')
                                    ->content(fn (?PartyUpdate $record): string => $record?->mailchimp_error ?: 'None'),
                            ])
                            ->visible(fn (Forms\Get $get): bool => in_array($get('publish_target'), [
                                PartyUpdate::PUBLISH_TARGET_EMAIL,
                                PartyUpdate::PUBLISH_TARGET_BOTH,
                            ], true))
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ]),
                    ]),
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
                Tables\Columns\TextColumn::make('email_subject')
                    ->label('Email Subject')
                    ->placeholder('Uses title')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Columns\TextColumn::make('mailchimp_status')
                    ->label('Mailchimp Status')
                    ->badge()
                    ->placeholder('Unknown')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'save' => 'Draft',
                        'sent' => 'Sent',
                        'sending' => 'Sending',
                        'schedule' => 'Scheduled',
                        default => $state ?: 'Unknown',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'sent' => 'success',
                        'sending', 'schedule' => 'warning',
                        'save' => 'gray',
                        default => 'gray',
                    })
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
