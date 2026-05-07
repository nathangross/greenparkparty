<?php

use App\Models\PartyUpdate;
use App\Services\MailchimpUpdateCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Newsletter\Facades\Newsletter;

uses(RefreshDatabase::class);

test('mailchimp update campaign service creates a local draft without calling mailchimp', function () {
    $update = PartyUpdate::factory()->create([
        'title' => 'RSVPs are open',
        'body' => '<p>Come join us.</p>',
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $campaignId = app(MailchimpUpdateCampaignService::class)->createDraft($update);

    $update->refresh();

    expect($campaignId)->toStartWith('local-')
        ->and($update->mailchimp_campaign_id)->toBe($campaignId)
        ->and($update->mailchimp_error)->toBeNull()
        ->and($update->mailchimp_sent_at)->toBeNull();
});

test('mailchimp update campaign service marks a local campaign as sent', function () {
    $update = PartyUpdate::factory()->create([
        'title' => 'RSVPs are open',
        'body' => '<p>Come join us.</p>',
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'is_published' => true,
        'published_at' => now(),
    ]);

    app(MailchimpUpdateCampaignService::class)->send($update);

    $update->refresh();

    expect($update->mailchimp_campaign_id)->toStartWith('local-')
        ->and($update->mailchimp_sent_at)->not->toBeNull()
        ->and($update->mailchimp_error)->toBeNull();
});

test('mailchimp update campaign service sends a local test without marking campaign sent', function () {
    $update = PartyUpdate::factory()->create([
        'title' => 'RSVPs are open',
        'body' => '<p>Come join us.</p>',
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'is_published' => true,
        'published_at' => now(),
    ]);

    app(MailchimpUpdateCampaignService::class)->sendTest($update, ['organizer@example.com']);

    $update->refresh();

    expect($update->mailchimp_campaign_id)->toStartWith('local-')
        ->and($update->mailchimp_sent_at)->toBeNull()
        ->and($update->mailchimp_error)->toBeNull();
});

test('mailchimp update campaign service refuses homepage only updates', function () {
    $update = PartyUpdate::factory()->create([
        'title' => 'Homepage only',
        'body' => '<p>This should not email.</p>',
        'publish_target' => PartyUpdate::PUBLISH_TARGET_HOMEPAGE,
        'is_published' => true,
        'published_at' => now(),
    ]);

    app(MailchimpUpdateCampaignService::class)->send($update);
})->throws(\RuntimeException::class, 'This update is not configured to publish to email.');

test('mailchimp update campaign service exposes the configured default list locally', function () {
    config(['newsletter.lists.subscribers.id' => 'default-list-id']);

    expect(app(MailchimpUpdateCampaignService::class)->mailchimpLists())
        ->toBe(['default-list-id' => 'Configured default audience']);
});

test('mailchimp update campaign service does not load segments locally', function () {
    config(['newsletter.lists.subscribers.id' => 'default-list-id']);

    expect(app(MailchimpUpdateCampaignService::class)->mailchimpSegments('default-list-id'))
        ->toBe([]);
});

test('mailchimp update campaign service creates campaigns for a selected mailchimp list', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'newsletter.lists.subscribers.id' => 'default-list-id',
        'mail.from.address' => 'noreply@example.com',
    ]);

    $api = new class
    {
        public array $campaignPayload = [];

        public function post(string $endpoint, array $payload = []): array
        {
            $this->campaignPayload = $payload;

            return ['id' => 'campaign-id'];
        }

        public function put(string $endpoint, array $payload = []): array
        {
            return [];
        }
    };

    Newsletter::shouldReceive('getApi')
        ->andReturn($api);

    $update = PartyUpdate::factory()->create([
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'mailchimp_list_id' => 'selected-list-id',
    ]);

    app(MailchimpUpdateCampaignService::class)->createDraft($update);

    expect($api->campaignPayload['recipients']['list_id'])->toBe('selected-list-id');
});

test('mailchimp update campaign service creates campaigns for a selected mailchimp segment', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'newsletter.lists.subscribers.id' => 'default-list-id',
        'mail.from.address' => 'noreply@example.com',
    ]);

    $api = new class
    {
        public array $campaignPayload = [];

        public function post(string $endpoint, array $payload = []): array
        {
            $this->campaignPayload = $payload;

            return ['id' => 'campaign-id'];
        }

        public function put(string $endpoint, array $payload = []): array
        {
            return [];
        }
    };

    Newsletter::shouldReceive('getApi')
        ->andReturn($api);

    $update = PartyUpdate::factory()->create([
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'mailchimp_list_id' => 'selected-list-id',
        'mailchimp_segment_id' => 123456,
    ]);

    app(MailchimpUpdateCampaignService::class)->createDraft($update);

    expect($api->campaignPayload['recipients'])
        ->toBe([
            'list_id' => 'selected-list-id',
            'segment_opts' => [
                'saved_segment_id' => 123456,
            ],
        ]);
});

test('mailchimp update campaign service loads segments for a selected mailchimp list', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'newsletter.lists.subscribers.id' => 'default-list-id',
        'mail.from.address' => 'noreply@example.com',
    ]);

    $api = new class
    {
        public string $endpoint = '';

        public function get(string $endpoint, array $payload = []): array
        {
            $this->endpoint = $endpoint;

            return [
                'segments' => [
                    [
                        'id' => 123456,
                        'name' => 'Neighbors',
                        'type' => 'static',
                        'member_count' => 42,
                    ],
                ],
            ];
        }
    };

    Newsletter::shouldReceive('getApi')
        ->andReturn($api);

    $segments = app(MailchimpUpdateCampaignService::class)->mailchimpSegments('selected-list-id');

    expect($api->endpoint)->toBe('lists/selected-list-id/segments')
        ->and($segments)->toBe([
            123456 => 'Neighbors - static (42 contacts)',
        ]);
});
