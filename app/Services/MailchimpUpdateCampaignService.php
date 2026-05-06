<?php

namespace App\Services;

use App\Models\PartyUpdate;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Newsletter\Facades\Newsletter;

class MailchimpUpdateCampaignService
{
    public function createDraft(PartyUpdate $update): string
    {
        $this->ensureConfigured();

        if (App::environment(['local', 'testing'])) {
            $campaignId = 'local-' . Str::uuid();
            $update->forceFill([
                'mailchimp_campaign_id' => $campaignId,
                'mailchimp_error' => null,
            ])->save();

            return $campaignId;
        }

        $mailchimp = Newsletter::getApi();
        $listId = config('newsletter.lists.subscribers.id');

        $campaign = $mailchimp->post('campaigns', [
            'type' => 'regular',
            'recipients' => [
                'list_id' => $listId,
            ],
            'settings' => [
                'subject_line' => $update->title,
                'title' => 'Green Park Party Update - ' . $update->title,
                'from_name' => config('mail.from.name', 'Green Park Party'),
                'reply_to' => config('mail.from.address'),
            ],
        ]);

        $campaignId = $campaign['id'] ?? null;

        if (!$campaignId) {
            throw new RuntimeException('Mailchimp did not return a campaign ID.');
        }

        $mailchimp->put("campaigns/{$campaignId}/content", [
            'html' => $this->htmlForUpdate($update),
        ]);

        $update->forceFill([
            'mailchimp_campaign_id' => $campaignId,
            'mailchimp_error' => null,
        ])->save();

        return $campaignId;
    }

    public function send(PartyUpdate $update): void
    {
        $this->ensureConfigured();

        if (!$update->mailchimp_campaign_id) {
            $this->createDraft($update);
            $update->refresh();
        }

        if (App::environment(['local', 'testing'])) {
            $update->forceFill([
                'mailchimp_sent_at' => now(),
                'mailchimp_error' => null,
            ])->save();

            return;
        }

        Newsletter::getApi()->post("campaigns/{$update->mailchimp_campaign_id}/actions/send");

        $update->forceFill([
            'mailchimp_sent_at' => now(),
            'mailchimp_error' => null,
        ])->save();
    }

    public function sendTest(PartyUpdate $update, array $emails): void
    {
        $this->ensureConfigured();

        if (!$update->mailchimp_campaign_id) {
            $this->createDraft($update);
            $update->refresh();
        }

        if (App::environment(['local', 'testing'])) {
            $update->forceFill(['mailchimp_error' => null])->save();

            return;
        }

        Newsletter::getApi()->post("campaigns/{$update->mailchimp_campaign_id}/actions/test", [
            'test_emails' => array_values($emails),
            'send_type' => 'html',
        ]);

        $update->forceFill(['mailchimp_error' => null])->save();
    }

    protected function ensureConfigured(): void
    {
        if (!config('newsletter.lists.subscribers.id')) {
            throw new RuntimeException('Mailchimp list ID is not configured.');
        }

        if (!config('mail.from.address')) {
            throw new RuntimeException('Mail from address is not configured.');
        }
    }

    protected function htmlForUpdate(PartyUpdate $update): string
    {
        $body = str($update->body)->sanitizeHtml();
        $url = route('welcome');

        return <<<HTML
<!doctype html>
<html>
<body style="margin:0;padding:0;background:#f1fbf7;color:#173f31;font-family:Arial,sans-serif;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <h1 style="font-size:32px;line-height:1.1;margin:0 0 20px;">{$update->title}</h1>
        <div style="font-size:17px;line-height:1.6;">{$body}</div>
        <p style="margin-top:28px;">
            <a href="{$url}" style="display:inline-block;background:#173f31;color:#ffffff;padding:12px 18px;border-radius:999px;text-decoration:none;font-weight:bold;">View party details</a>
        </p>
    </div>
</body>
</html>
HTML;
    }
}
