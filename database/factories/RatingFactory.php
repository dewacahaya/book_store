<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rating>
 */
class RatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::inRandomOrder()->first()->id ?? Book::factory(),
            'user_identifier' => $this->faker->unique()->safeEmail(),
            'rating' => $this->faker->numberBetween(1, 10),
            'created_at' => $this->faker->dateTimeBetween('-60 days', 'now'),
        ];
    }
}
