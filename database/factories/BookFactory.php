<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => Author::inRandomOrder()->first()->id ?? Author::factory(),
            'title' => $this->faker->sentence(3),
            'isbn' => $this->faker->unique()->isbn13(),
            'publisher' => $this->faker->company(),
            'publication_year' => $this->faker->year(),
            'availability' => $this->faker->randomElement(['available', 'rented', 'reserved']),
            'store_location' => $this->faker->randomElement(['Jakarta', 'Bali', 'Bandung', 'Surabaya']),
            'description' => $this->faker->paragraph(),
        ];
    }
}
