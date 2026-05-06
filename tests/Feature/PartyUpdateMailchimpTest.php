<?php

use App\Models\PartyUpdate;
use App\Services\MailchimpUpdateCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('mailchimp update campaign service creates a local draft without calling mailchimp', function () {
    $update = PartyUpdate::factory()->create([
        'title' => 'RSVPs are open',
        'body' => '<p>Come join us.</p>',
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
        'is_published' => true,
        'published_at' => now(),
    ]);

    app(MailchimpUpdateCampaignService::class)->sendTest($update, ['organizer@example.com']);

    $update->refresh();

    expect($update->mailchimp_campaign_id)->toStartWith('local-')
        ->and($update->mailchimp_sent_at)->toBeNull()
        ->and($update->mailchimp_error)->toBeNull();
});
