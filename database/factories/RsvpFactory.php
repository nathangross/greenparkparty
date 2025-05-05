<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'party_id' => Party::factory(),
            'user_id' => User::factory(),
            'attending_count' => $this->faker->numberBetween(0, 5),
            'volunteer' => $this->faker->boolean(),
            'message_text' => $this->faker->optional()->sentence(),
            'receive_email_updates' => $this->faker->boolean(),
            'receive_sms_updates' => $this->faker->boolean(),
        ];
    }
}
