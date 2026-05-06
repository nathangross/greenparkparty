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
            'body' => $this->faker->paragraph(),
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
