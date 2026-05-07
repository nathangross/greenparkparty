<?php

namespace App\Services;

use App\Models\PartyUpdate;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Newsletter\Facades\Newsletter;

class MailchimpUpdateCampaignService
{
    public function createDraft(PartyUpdate $update): string
    {
        $this->ensureConfigured();
        $this->ensureEmailTarget($update);

        if (App::environment(['local', 'testing'])) {
            $campaignId = 'local-'.Str::uuid();
            $update->forceFill([
                'mailchimp_campaign_id' => $campaignId,
                'mailchimp_error' => null,
            ])->save();

            return $campaignId;
        }

        $mailchimp = Newsletter::getApi();
        $listId = $this->listIdForUpdate($update);

        $campaign = $mailchimp->post('campaigns', [
            'type' => 'regular',
            'recipients' => $this->recipientsForUpdate($update, $listId),
            'settings' => [
                'subject_line' => $update->title,
                'title' => 'Green Park Party Update - '.$update->title,
                'from_name' => config('mail.from.name', 'Green Park Party'),
                'reply_to' => config('mail.from.address'),
            ],
        ]);

        $campaignId = $campaign['id'] ?? null;

        if (! $campaignId) {
            throw new RuntimeException('Mailchimp did not return a campaign ID.');
        }

        $this->updateCampaignContent($campaignId, $update);

        $update->forceFill([
            'mailchimp_campaign_id' => $campaignId,
            'mailchimp_error' => null,
        ])->save();

        return $campaignId;
    }

    public function send(PartyUpdate $update): void
    {
        $this->ensureConfigured();
        $this->ensureEmailTarget($update);

        if (! $update->mailchimp_campaign_id) {
            $this->createDraft($update);
            $update->refresh();
        } elseif (! App::environment(['local', 'testing'])) {
            $this->updateCampaignContent($update->mailchimp_campaign_id, $update);
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
        $this->ensureEmailTarget($update);

        if (! $update->mailchimp_campaign_id) {
            $this->createDraft($update);
            $update->refresh();
        } elseif (! App::environment(['local', 'testing'])) {
            $this->updateCampaignContent($update->mailchimp_campaign_id, $update);
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

    public function mailchimpLists(): array
    {
        $defaultListId = config('newsletter.lists.subscribers.id');

        if (App::environment(['local', 'testing'])) {
            return $defaultListId ? [$defaultListId => 'Configured default audience'] : [];
        }

        $this->ensureConfigured();

        return Cache::remember('mailchimp.lists', now()->addMinutes(10), function (): array {
            $response = Newsletter::getApi()->get('lists', [
                'count' => 100,
                'fields' => 'lists.id,lists.name,lists.stats.member_count',
            ]);

            return collect($response['lists'] ?? [])
                ->mapWithKeys(function (array $list): array {
                    $memberCount = $list['stats']['member_count'] ?? null;
                    $label = $list['name'] ?? $list['id'];

                    if ($memberCount !== null) {
                        $label .= ' ('.number_format($memberCount).' contacts)';
                    }

                    return [$list['id'] => $label];
                })
                ->all();
        });
    }

    public function mailchimpSegments(?string $listId = null): array
    {
        $listId = $listId ?: config('newsletter.lists.subscribers.id');

        if (! $listId || App::environment(['local', 'testing'])) {
            return [];
        }

        $this->ensureConfigured();

        return Cache::remember("mailchimp.lists.{$listId}.segments", now()->addMinutes(10), function () use ($listId): array {
            $response = Newsletter::getApi()->get("lists/{$listId}/segments", [
                'count' => 100,
                'fields' => 'segments.id,segments.name,segments.type,segments.member_count',
            ]);

            return collect($response['segments'] ?? [])
                ->mapWithKeys(function (array $segment): array {
                    $memberCount = $segment['member_count'] ?? null;
                    $type = $segment['type'] ?? null;
                    $label = $segment['name'] ?? $segment['id'];

                    if ($type) {
                        $label .= ' - '.$type;
                    }

                    if ($memberCount !== null) {
                        $label .= ' ('.number_format($memberCount).' contacts)';
                    }

                    return [$segment['id'] => $label];
                })
                ->all();
        });
    }

    protected function ensureConfigured(): void
    {
        if (! config('newsletter.lists.subscribers.id')) {
            throw new RuntimeException('Mailchimp list ID is not configured.');
        }

        if (! config('mail.from.address')) {
            throw new RuntimeException('Mail from address is not configured.');
        }
    }

    protected function ensureEmailTarget(PartyUpdate $update): void
    {
        if (! $update->publishesToEmail()) {
            throw new RuntimeException('This update is not configured to publish to email.');
        }
    }

    protected function listIdForUpdate(PartyUpdate $update): string
    {
        return $update->mailchimp_list_id ?: config('newsletter.lists.subscribers.id');
    }

    protected function recipientsForUpdate(PartyUpdate $update, string $listId): array
    {
        $recipients = ['list_id' => $listId];

        if ($update->mailchimp_segment_id) {
            $recipients['segment_opts'] = [
                'saved_segment_id' => (int) $update->mailchimp_segment_id,
            ];
        }

        return $recipients;
    }

    protected function updateCampaignContent(string $campaignId, PartyUpdate $update): void
    {
        Newsletter::getApi()->put("campaigns/{$campaignId}/content", [
            'html' => $this->previewHtml($update),
        ]);
    }

    public function previewHtml(PartyUpdate $update): string
    {
        $update->loadMissing('party');

        $body = str($update->body)->sanitizeHtml();
        $title = e($update->title);
        $url = route('welcome');
        $rsvpUrl = route('welcome').'#rsvp';
        $logoUrl = asset('images/logo-universal-reverse@2x.png');
        $preheader = e(str($update->body)->stripTags()->squish()->limit(140));
        $party = $update->party;
        $date = $party?->primary_date_start?->format('F j, Y') ?? 'Date to be announced';
        $time = $party && $party->primary_date_start && $party->primary_date_end
            ? $party->primary_date_start->format('g:i A').' to '.$party->primary_date_end->format('g:i A')
            : 'Time to be announced';
        $makeup = $party?->secondary_date_start
            ? 'Makeup date: '.$party->secondary_date_start->format('F j, Y').($party->secondary_date_end ? ', '.$party->secondary_date_start->format('g:i A').' to '.$party->secondary_date_end->format('g:i A') : '')
            : null;
        $makeupHtml = $makeup ? '<div style="margin-top:8px;color:#567568;font-size:14px;line-height:20px;">'.e($makeup).'</div>' : '';

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#d9f4ea;color:#173f31;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{$preheader}</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#d9f4ea;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;margin:0 auto;">
                    <tr>
                        <td align="center" style="padding:18px 0 26px;">
                            <a href="{$url}" style="text-decoration:none;">
                                <img src="{$logoUrl}" width="260" alt="Green Park Party" style="display:block;width:260px;max-width:78%;height:auto;border:0;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;border:1px solid rgba(23,63,49,0.12);border-radius:28px;box-shadow:0 14px 35px rgba(23,63,49,0.10);overflow:hidden;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="background:#52b77f;padding:28px 30px;text-align:center;">
                                        <div style="color:#173f31;font-size:13px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;">Green Park Party Update</div>
                                        <h1 style="margin:10px 0 0;color:#ffffff;font-size:38px;line-height:42px;font-weight:800;">{$title}</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:30px;">
                                        <div style="background:#f1fbf7;border:1px solid rgba(23,63,49,0.10);border-radius:18px;padding:18px 20px;margin-bottom:26px;text-align:center;">
                                            <div style="font-size:22px;line-height:28px;font-weight:800;color:#173f31;">{$date}</div>
                                            <div style="margin-top:4px;font-size:17px;line-height:24px;color:#244d3d;">{$time}</div>
                                            <div style="margin-top:14px;font-size:16px;line-height:23px;color:#173f31;font-weight:700;">Green Park Shelter</div>
                                            <div style="font-size:14px;line-height:20px;color:#567568;">6661 Green Park Drive, Dayton OH 45459</div>
                                            {$makeupHtml}
                                        </div>
                                        <div style="font-size:17px;line-height:28px;color:#244d3d;">{$body}</div>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:30px auto 8px;">
                                            <tr>
                                                <td align="center" bgcolor="#173f31" style="border-radius:999px;">
                                                    <a href="{$rsvpUrl}" style="display:inline-block;padding:14px 22px;color:#ffffff;font-size:16px;line-height:20px;font-weight:800;text-decoration:none;border-radius:999px;">RSVP / View party details</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:22px 20px 0;color:#567568;font-size:13px;line-height:20px;">
                            <div>Invite your neighbors. We hope to see you there.</div>
                            <div style="margin-top:8px;"><a href="{$url}" style="color:#173f31;font-weight:700;text-decoration:underline;">greenparkparty.com</a></div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
