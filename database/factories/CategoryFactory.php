<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->randomElement([
                'Fiction',
                'Science',
                'Romance',
                'Thriller',
                'Mystery',
                'Biography',
                'Fantasy',
                'Education',
                'History',
                'Art'
            ])) . ' #' . $this->faker->numberBetween(1, 3000),
        ];
    }
}
