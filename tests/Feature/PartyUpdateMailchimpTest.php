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

test('mailchimp update campaign service refreshes existing campaign settings before sending a test', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'newsletter.lists.subscribers.id' => 'default-list-id',
        'mail.from.address' => 'noreply@example.com',
    ]);

    $api = new class
    {
        public array $patchPayload = [];

        public array $putPayload = [];

        public array $testPayload = [];

        public function patch(string $endpoint, array $payload = []): array
        {
            $this->patchPayload = $payload;

            return [];
        }

        public function put(string $endpoint, array $payload = []): array
        {
            $this->putPayload = $payload;

            return [];
        }

        public function post(string $endpoint, array $payload = []): array
        {
            $this->testPayload = $payload;

            return [];
        }
    };

    Newsletter::shouldReceive('getApi')
        ->andReturn($api);

    $update = PartyUpdate::factory()->create([
        'title' => 'Green Park Party 2026',
        'email_subject' => null,
        'body' => '<p>Come join us.</p>',
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'mailchimp_campaign_id' => 'existing-campaign-id',
    ]);

    app(MailchimpUpdateCampaignService::class)->sendTest($update, ['organizer@example.com']);

    expect($api->patchPayload['settings']['subject_line'])->toBe('Green Park Party 2026')
        ->and($api->patchPayload['settings']['title'])->toBe('Green Park Party Update - Green Park Party 2026')
        ->and($api->putPayload['html'])->toContain('Green Park Party 2026')
        ->and($api->testPayload)->toBe([
            'test_emails' => ['organizer@example.com'],
            'send_type' => 'html',
        ]);
});

test('mailchimp update campaign service uses custom email subject when present', function () {
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
        'title' => 'Green Park Party 2026',
        'email_subject' => 'RSVPs are open for 2026!',
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
    ]);

    app(MailchimpUpdateCampaignService::class)->createDraft($update);

    expect($api->campaignPayload['settings']['subject_line'])->toBe('RSVPs are open for 2026!')
        ->and($api->campaignPayload['settings']['title'])->toBe('Green Park Party Update - Green Park Party 2026');
});

test('mailchimp update campaign service does not mark update sent when mailchimp send fails', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'newsletter.lists.subscribers.id' => 'default-list-id',
        'mail.from.address' => 'noreply@example.com',
    ]);

    $api = new class
    {
        public bool $successful = true;

        public function patch(string $endpoint, array $payload = []): array
        {
            $this->successful = true;

            return [];
        }

        public function put(string $endpoint, array $payload = []): array
        {
            $this->successful = true;

            return [];
        }

        public function post(string $endpoint, array $payload = []): array
        {
            $this->successful = false;

            return [
                'status' => 400,
                'detail' => 'Campaign cannot be sent because the send checklist is incomplete.',
            ];
        }

        public function success(): bool
        {
            return $this->successful;
        }

        public function getLastError(): string
        {
            return 'Campaign cannot be sent because the send checklist is incomplete.';
        }
    };

    Newsletter::shouldReceive('getApi')
        ->andReturn($api);

    $update = PartyUpdate::factory()->create([
        'title' => 'Green Park Party 2026',
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'mailchimp_campaign_id' => 'existing-campaign-id',
        'mailchimp_sent_at' => null,
    ]);

    app(MailchimpUpdateCampaignService::class)->send($update);
})->throws(
    \RuntimeException::class,
    'Failed to send Mailchimp campaign: Campaign cannot be sent because the send checklist is incomplete.'
);

test('mailchimp update campaign service syncs sent status from mailchimp', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'newsletter.lists.subscribers.id' => 'default-list-id',
        'mail.from.address' => 'noreply@example.com',
    ]);

    $api = new class
    {
        public function get(string $endpoint, array $payload = []): array
        {
            return [
                'id' => 'existing-campaign-id',
                'status' => 'sent',
                'send_time' => '2026-05-14T14:30:00+00:00',
            ];
        }

        public function success(): bool
        {
            return true;
        }
    };

    Newsletter::shouldReceive('getApi')
        ->andReturn($api);

    $update = PartyUpdate::factory()->create([
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'mailchimp_campaign_id' => 'existing-campaign-id',
        'mailchimp_status' => 'save',
        'mailchimp_sent_at' => null,
    ]);

    $status = app(MailchimpUpdateCampaignService::class)->syncCampaignStatus($update);

    $update->refresh();

    expect($status['status'])->toBe('sent')
        ->and($update->mailchimp_status)->toBe('sent')
        ->and($update->mailchimp_sent_at?->toIso8601String())->toBe('2026-05-14T14:30:00+00:00');
});

test('mailchimp update campaign service clears local sent state when mailchimp is still draft', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'newsletter.lists.subscribers.id' => 'default-list-id',
        'mail.from.address' => 'noreply@example.com',
    ]);

    $api = new class
    {
        public function get(string $endpoint, array $payload = []): array
        {
            return [
                'id' => 'existing-campaign-id',
                'status' => 'save',
                'send_time' => null,
            ];
        }

        public function success(): bool
        {
            return true;
        }
    };

    Newsletter::shouldReceive('getApi')
        ->andReturn($api);

    $update = PartyUpdate::factory()->create([
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'mailchimp_campaign_id' => 'existing-campaign-id',
        'mailchimp_status' => 'sent',
        'mailchimp_sent_at' => now(),
    ]);

    $status = app(MailchimpUpdateCampaignService::class)->syncCampaignStatus($update);

    $update->refresh();

    expect($status['status'])->toBe('save')
        ->and($update->mailchimp_status)->toBe('save')
        ->and($update->mailchimp_sent_at)->toBeNull();
});

test('mailchimp update campaign service summarizes send details', function () {
    config(['newsletter.lists.subscribers.id' => 'default-list-id']);

    $update = PartyUpdate::factory()->create([
        'title' => 'Green Park Party 2026',
        'email_subject' => 'RSVPs are open for 2026!',
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'mailchimp_list_id' => 'default-list-id',
        'mailchimp_segment_id' => null,
        'mailchimp_campaign_id' => null,
    ]);

    expect(app(MailchimpUpdateCampaignService::class)->sendSummary($update))->toBe([
        'subject' => 'RSVPs are open for 2026!',
        'audience' => 'Configured default audience',
        'segment' => 'Whole audience',
        'campaign' => 'Green Park Party Update - Green Park Party 2026',
        'campaign_status' => 'new',
    ]);
});

test('mailchimp update campaign service summarizes selected audience segment and existing draft', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'newsletter.lists.subscribers.id' => 'default-list-id',
        'mail.from.address' => 'noreply@example.com',
    ]);

    $api = new class
    {
        public function get(string $endpoint, array $payload = []): array
        {
            return match ($endpoint) {
                'lists' => [
                    'lists' => [
                        [
                            'id' => 'selected-list-id',
                            'name' => 'Green Park Party',
                            'stats' => [
                                'member_count' => 45,
                            ],
                        ],
                    ],
                ],
                'lists/selected-list-id/segments' => [
                    'segments' => [
                        [
                            'id' => 123456,
                            'name' => '2026 - Volunteers',
                            'type' => 'static',
                            'member_count' => 12,
                        ],
                    ],
                ],
                default => [],
            };
        }
    };

    Newsletter::shouldReceive('getApi')
        ->andReturn($api);

    $update = PartyUpdate::factory()->create([
        'title' => 'Green Park Party 2026',
        'email_subject' => null,
        'publish_target' => PartyUpdate::PUBLISH_TARGET_EMAIL,
        'mailchimp_list_id' => 'selected-list-id',
        'mailchimp_segment_id' => 123456,
        'mailchimp_campaign_id' => 'existing-campaign-id',
    ]);

    expect(app(MailchimpUpdateCampaignService::class)->sendSummary($update))->toBe([
        'subject' => 'Green Park Party 2026',
        'audience' => 'Green Park Party (45 contacts)',
        'segment' => '2026 - Volunteers - static (12 contacts)',
        'campaign' => 'Green Park Party Update - Green Park Party 2026',
        'campaign_status' => 'update existing',
    ]);
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
