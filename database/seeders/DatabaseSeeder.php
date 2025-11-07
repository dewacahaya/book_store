<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Category;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $now = Carbon::now();

        // ====== 1️⃣ Reset tabel dengan aman ======
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach (['authors', 'categories', 'books', 'ratings', 'book_category'] as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ====== 2️⃣ Authors ======
        echo "Seeding authors...\n";
        $authors = collect(range(1, 1000))->map(fn() => [
            'name' => $faker->name,
            'bio' => $faker->sentence(10),
            'created_at' => $now,
            'updated_at' => $now,
        ])->chunk(500)->each(fn($chunk) => DB::table('authors')->insert($chunk->toArray()));

        // ====== 3️⃣ Categories ======
        echo "Seeding categories...\n";
        $baseCategories = ['Fiction','Science','Romance','Thriller','Mystery','Biography','Fantasy','Education','History','Art'];
        $categories = collect(range(1, 3000))->map(fn() => [
            'name' => ucfirst($faker->randomElement($baseCategories)) . ' #' . $faker->unique()->numberBetween(1, 9999),
            'created_at' => $now,
            'updated_at' => $now,
        ])->chunk(1000)->each(fn($chunk) => DB::table('categories')->insert($chunk->toArray()));

        // ====== 4️⃣ Books ======
        echo "Seeding books...\n";
        $totalBooks = 100000;
        $batchSize = 2000;
        $numBatches = (int) ceil($totalBooks / $batchSize);

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4096M');

        for ($i = 0; $i <= $numBatches; $i++) {
            $books = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $books[] = [
                    'author_id' => mt_rand(1, 1000),
                    'title' => $faker->sentence(3),
                    'isbn' => 'ISBN-' . str_pad($i * $batchSize + $j + 1, 8, '0', STR_PAD_LEFT),
                    'publisher' => $faker->randomElement(['Gramedia', 'Erlangga', 'Bentang', 'Mizan', 'KPG', 'Elex Media']),
                    'publication_year' => $faker->numberBetween(1990, 2025),
                    'availability' => $faker->randomElement(['available', 'rented', 'reserved']),
                    'store_location' => $faker->randomElement(['Jakarta', 'Bali', 'Bandung', 'Surabaya']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('books')->insert($books);
            echo "Inserted books batch " . ($i + 1) . "/{$numBatches}\n";

            // Flush Faker memory
            $faker->unique($reset = true);
            unset($books);
        }

        echo "✅ Books seeding completed ({$totalBooks})\n";


        // ====== 5️⃣ Book_Category Pivot ======
        echo "Seeding pivot book_category...\n";
        $pivotBatch = [];
        $catIds = DB::table('categories')->pluck('id')->toArray();

        // gunakan cursor agar tidak load semua buku sekaligus
        DB::table('books')->orderBy('id')->select('id')->chunk(2000, function ($books) use (&$pivotBatch, $catIds) {
            foreach ($books as $book) {
                $randomCats = array_rand($catIds, mt_rand(1, 3));
                $randomCats = (array) $randomCats; // handle single index
                foreach ($randomCats as $key) {
                    $pivotBatch[] = [
                        'book_id' => $book->id,
                        'category_id' => $catIds[$key],
                    ];
                }
            }

            DB::table('book_category')->insert($pivotBatch);
            $pivotBatch = [];
            echo ".";
        });

        echo "\n✅ Book-Category pivot done!\n";


        // ====== 6️⃣ Ratings ======
        echo "Seeding ratings...\n";
        $totalRatings = 500000;
        $batchSize = 5000;
        $numBatches = (int) ceil($totalRatings / $batchSize);

        for ($i = 0; $i <= $numBatches; $i++) {
            $ratings = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $ratings[] = [
                    'book_id' => mt_rand(1, $totalBooks),
                    'user_identifier' => 'user_' . mt_rand(1, 10000),
                    'rating' => mt_rand(1, 10),
                    'created_at' => Carbon::now()->subDays(mt_rand(0, 60)), // sebar waktu rating realistis
                    'updated_at' => $now,
                ];
            }

            DB::table('ratings')->insert($ratings);
            unset($ratings);

            if ($i % 10 === 0) {
                echo "Inserted rating batch " . ($i + 1) . "/{$numBatches}\n";
            }
        }

        echo "✅ Ratings seeding completed ({$totalRatings})\n";
    }
}
