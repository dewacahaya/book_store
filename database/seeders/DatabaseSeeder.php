<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Author;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('authors')->truncate();
        DB::table('categories')->truncate();
        DB::table('books')->truncate();
        DB::table('ratings')->truncate();
        DB::table('book_category')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // =============== AUTHORS ===============
        $authors = [];
        for ($i = 0; $i < 1000; $i++) {
            $authors[] = [
                'name' => $faker->name,
                'bio' => $faker->sentence(10),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('authors')->insert($authors);

        // =============== CATEGORIES ===============
        $categories = [];
        for ($i = 0; $i < 3000; $i++) {
            $categories[] = [
                'name' => ucfirst($faker->randomElement([
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
                ])) . ' #' . $faker->numberBetween(1, 9999),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('categories')->insert($categories);

        // =============== BOOKS (chunked insert) ===============
        $totalBooks = 100000;
        $batchSize = 1000;
        for ($i = 0; $i < $totalBooks / $batchSize; $i++) {
            $books = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $books[] = [
                    'author_id' => rand(1, 1000),
                    'title' => $faker->sentence(3),
                    'isbn' => 'ISBN-' . str_pad($faker->unique()->numberBetween(1, 1000000), 7, '0', STR_PAD_LEFT),
                    'publication_year' => $faker->numberBetween(1990, 2025),
                    'availability' => $faker->randomElement(['available', 'rented', 'reserved']),
                    'store_location' => $faker->randomElement(['Jakarta', 'Bali', 'Bandung', 'Surabaya']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('books')->insert($books);
        }

        // =============== BOOK CATEGORY PIVOT ===============
        $bookCount = DB::table('books')->count();
        $pivotData = [];
        for ($i = 1; $i <= $bookCount; $i++) {
            $categories = Category::inRandomOrder()->take(rand(1, 3))->pluck('id')->toArray();
            foreach ($categories as $catId) {
                $pivotData[] = [
                    'book_id' => $i,
                    'category_id' => $catId,
                ];
            }
            if ($i % 1000 == 0) {
                DB::table('book_category')->insert($pivotData);
                $pivotData = [];
            }
        }

        // =============== RATINGS (chunked insert) ===============
        $totalRatings = 500000;
        $batchSize = 5000;
        for ($i = 0; $i < $totalRatings / $batchSize; $i++) {
            $ratings = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $ratings[] = [
                    'book_id' => rand(1, 100000),
                    'user_identifier' => 'user_' . rand(1, 10000),
                    'rating' => rand(1, 10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('ratings')->insert($ratings);
        }

        $this->command->info('✅ Seeding selesai dengan sukses!');
    }
}
