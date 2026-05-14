<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\PartyUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartyUpdateFactory extends Factory
{
    protected $model = PartyUpdate::class;

    public function definition(): array
    {
        return [
            'party_id' => Party::factory(),
            'title' => $this->faker->sentence(4),
            'email_subject' => null,
            'body' => $this->faker->paragraph(),
            'publish_target' => PartyUpdate::PUBLISH_TARGET_HOMEPAGE,
            'mailchimp_list_id' => null,
            'mailchimp_segment_id' => null,
            'mailchimp_status' => null,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
