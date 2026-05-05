<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Party;

class PartyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Party::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $primaryDateStart = now()->addWeeks(6)->setTime(17, 0);
        $primaryDateEnd = $primaryDateStart->copy()->addHours(3);
        $secondaryDateStart = $primaryDateStart->copy()->addDay();
        $secondaryDateEnd = $secondaryDateStart->copy()->addHours(3);
        $hasSecondaryDate = $this->faker->boolean(75);

        return [
            'title' => $this->faker->sentence(4),
            'primary_date_start' => $primaryDateStart,
            'primary_date_end' => $primaryDateEnd,
            'secondary_date_start' => $hasSecondaryDate ? $secondaryDateStart : null,
            'secondary_date_end' => $hasSecondaryDate ? $secondaryDateEnd : null,
        ];
    }
}
