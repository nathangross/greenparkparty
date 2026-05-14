<?php

namespace App\Filament\Resources\PartyUpdateResource\Pages;

use App\Filament\Resources\PartyUpdateResource;
use App\Services\MailchimpUpdateCampaignService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;

class EditPartyUpdate extends EditRecord
{
    protected static string $resource = PartyUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('previewMailchimpEmail')
                ->label('Preview Email')
                ->icon('heroicon-o-eye')
                ->visible(fn (): bool => $this->record->publishesToEmail())
                ->modalHeading('Mailchimp email preview')
                ->modalDescription('This preview uses the saved update content and does not create, test, or send a Mailchimp campaign.')
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn (MailchimpUpdateCampaignService $mailchimp): View => view('filament.party-update-email-preview', [
                    'html' => $mailchimp->previewHtml($this->record),
                ])),
            Actions\Action::make('createMailchimpDraft')
                ->label('Create Mailchimp Draft')
                ->icon('heroicon-o-envelope')
                ->visible(fn (): bool => $this->record->publishesToEmail() && ! $this->record->mailchimp_campaign_id)
                ->requiresConfirmation()
                ->action(function (MailchimpUpdateCampaignService $mailchimp): void {
                    $this->save();

                    try {
                        $mailchimp->createDraft($this->record);
                        $this->refreshFormData([
                            'mailchimp_campaign_id',
                            'mailchimp_status',
                            'mailchimp_sent_at',
                            'mailchimp_error',
                        ]);

                        Notification::make()
                            ->title('Mailchimp draft created')
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        $this->record->forceFill(['mailchimp_error' => $exception->getMessage()])->save();

                        Notification::make()
                            ->title('Mailchimp draft failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('syncMailchimpStatus')
                ->label('Sync Mailchimp Status')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => $this->record->publishesToEmail() && filled($this->record->mailchimp_campaign_id))
                ->action(function (MailchimpUpdateCampaignService $mailchimp): void {
                    try {
                        $status = $mailchimp->syncCampaignStatus($this->record);
                        $this->refreshFormData([
                            'mailchimp_status',
                            'mailchimp_sent_at',
                            'mailchimp_error',
                        ]);

                        Notification::make()
                            ->title('Mailchimp status synced')
                            ->body('Mailchimp status: '.($status['status'] ?: 'unknown').'.')
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        $this->record->forceFill(['mailchimp_error' => $exception->getMessage()])->save();

                        Notification::make()
                            ->title('Mailchimp status sync failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('sendMailchimp')
                ->label('Send Mailchimp')
                ->icon('heroicon-o-paper-airplane')
                ->color('danger')
                ->visible(fn (): bool => $this->record->publishesToEmail() && ! $this->record->mailchimp_sent_at && ! $this->record->hasMailchimpSendInProgress())
                ->requiresConfirmation()
                ->modalHeading('Send this email update now?')
                ->modalDescription('Review the subject, audience, and segment before sending.')
                ->modalContent(fn (MailchimpUpdateCampaignService $mailchimp): View => view('filament.party-update-send-confirmation', [
                    'summary' => $mailchimp->sendSummary($this->record),
                ]))
                ->action(function (MailchimpUpdateCampaignService $mailchimp): void {
                    $this->save();

                    try {
                        $mailchimp->send($this->record);
                        $this->refreshFormData([
                            'mailchimp_campaign_id',
                            'mailchimp_status',
                            'mailchimp_sent_at',
                            'mailchimp_error',
                        ]);

                        Notification::make()
                            ->title('Mailchimp campaign sent')
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        $this->record->forceFill(['mailchimp_error' => $exception->getMessage()])->save();

                        Notification::make()
                            ->title('Mailchimp send failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('sendMailchimpTest')
                ->label('Send Test Email')
                ->icon('heroicon-o-envelope-open')
                ->visible(fn (): bool => $this->record->publishesToEmail())
                ->form([
                    Forms\Components\TextInput::make('email')
                        ->label('Test email')
                        ->email()
                        ->required()
                        ->default(fn (): ?string => auth()->user()?->email),
                ])
                ->modalHeading('Send a Mailchimp test email?')
                ->modalDescription('This saves the update, creates a Mailchimp draft if needed, then sends a test email only. It does not send the campaign to subscribers.')
                ->action(function (array $data, MailchimpUpdateCampaignService $mailchimp): void {
                    $this->save();

                    try {
                        $mailchimp->sendTest($this->record, [$data['email']]);
                        $this->refreshFormData([
                            'mailchimp_campaign_id',
                            'mailchimp_status',
                            'mailchimp_sent_at',
                            'mailchimp_error',
                        ]);

                        Notification::make()
                            ->title('Mailchimp test email sent')
                            ->body("Sent to {$data['email']}.")
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        $this->record->forceFill(['mailchimp_error' => $exception->getMessage()])->save();

                        Notification::make()
                            ->title('Mailchimp test failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
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
