<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Parties, id;
use App\Models\Rsvp;

class RsvpFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rsvp::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'party_id' => Parties, id::factory(),
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->text(),
            'has_many' => $this->faker->word(),
            'soft_deletes' => $this->faker->word(),
        ];
    }
}
