<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;
use App\Models\Author;
use App\Models\Rating;


class BookController extends Controller
{
    public function index(Request $request)
    {
        $userIdentifier = $request->ip();
        $thirtyDaysAgo = now()->subDays(30);
        $sevenDaysAgo = now()->subDays(7);

        // Query dasar dengan eager loading + agregasi
        $query = Book::with(['author:id,name', 'categories:id,name'])
            ->withCount(['ratings as ratings_count'])
            ->withAvg('ratings as avg_rating', 'rating')
            ->withCount([
                'ratings as recent_count' => function ($q) use ($thirtyDaysAgo) {
                    $q->where('created_at', '>=', $thirtyDaysAgo);
                }
            ]);

        // ===============================================
        // ✅ BARU: Tambahkan agregasi 7 hari untuk Tren Rating
        // ===============================================

        // Rata-rata 7 hari terakhir
        $query->withAvg([
            'ratings as avg_rating_7d' => function ($q) use ($sevenDaysAgo) {
                $q->where('created_at', '>=', $sevenDaysAgo);
            }
        ], 'rating');

        // Rata-rata sebelum 7 hari (semua data sebelum 7 hari yang lalu)
        $query->withAvg([
            'ratings as avg_rating_pre7d' => function ($q) use ($sevenDaysAgo) {
                $q->where('created_at', '<', $sevenDaysAgo);
            }
        ], 'rating');

        // ... (Filter dan Sorting tetap sama) ...

        /**
         * 🔎 Filter kategori (AND/OR)
         */
        if ($request->filled('categories')) {
            $categoryNames = (array) $request->input('categories');
            $logic = $request->input('category_logic', 'or');

            if ($logic === 'and') {
                foreach ($categoryNames as $name) {
                    $query->whereHas(
                        'categories',
                        fn($q) =>
                        $q->where('categories.name', 'like', "{$name}%")
                    );
                }
            } else {
                $query->whereHas(
                    'categories',
                    fn($q) =>
                    $q->where(
                        fn($q2) =>
                        collect($categoryNames)->each(
                            fn($name) =>
                            $q2->orWhere('categories.name', 'like', "{$name}%")
                        )
                    )
                );
            }
        }

        /**
         * 🎯 Filter sederhana
         */
        foreach (['author_id', 'availability', 'store_location'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->$field);
            }
        }

        /**
         * 📅 Filter range tahun & rating
         */
        if ($request->filled('year_min')) {
            $query->where('publication_year', '>=', intval($request->year_min));
        }
        if ($request->filled('year_max')) {
            $query->where('publication_year', '<=', intval($request->year_max));
        }
        // Menggunakan having untuk filtering berdasarkan hasil agregasi
        if ($request->filled('rating_min')) {
            $query->having('avg_rating', '>=', floatval($request->rating_min));
        }
        if ($request->filled('rating_max')) {
            $query->having('avg_rating', '<=', floatval($request->rating_max));
        }

        /**
         * 🔍 Pencarian bebas
         */
        if ($request->filled('keyword')) {
            $keyword = "%{$request->keyword}%";
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', $keyword)
                    ->orWhere('isbn', 'like', $keyword)
                    ->orWhere('publisher', 'like', $keyword)
                    ->orWhereHas('author', fn($a) => $a->where('name', 'like', $keyword));
            });
        }

        /**
         * 📊 Sorting
         */
        // Perhatian: Sorting 'trending' dilakukan setelah load data karena perhitungannya di PHP
        if ($request->input('sort_by') === 'alphabetical') {
            $query->orderBy('title');
        } elseif ($request->input('sort_by') === 'votes') {
            $query->orderByDesc('ratings_count');
        } elseif ($request->input('sort_by') === 'popularity') {
            $query->orderByDesc('recent_count');
        } else {
            // Default: Weighted Rating (menggunakan avg_rating)
            $query->orderByDesc('avg_rating');
        }


        /**
         * 📄 Pagination dan Transformasi
         */
        $books = $query->simplePaginate(10)->appends($request->query());

        // ✅ BARU: Hitung rating_change_7d di PHP
        $books->getCollection()->transform(function ($book) {
            $avgRecent = $book->avg_rating_7d;
            $avgPrevious = $book->avg_rating_pre7d;
            $avgGlobal = $book->avg_rating ?? 5.0; // Fallback jika tidak ada rating sama sekali

            // Logika Fallback Cerdas:
            if ($avgRecent === null && $avgPrevious === null) {
                // Tidak ada voting 7 hari terakhir, perubahan 0
                $change = 0;
            } elseif ($avgRecent !== null && $avgPrevious === null) {
                // Ada voting baru, tapi tidak ada voting lama: bandingkan dengan rata-rata global
                $change = $avgRecent - $avgGlobal;
            } elseif ($avgRecent === null && $avgPrevious !== null) {
                // Ada voting lama, tapi tidak ada voting baru: bandingkan rata-rata global dengan rata-rata lama
                $change = $avgGlobal - $avgPrevious;
            } else {
                // Kondisi ideal: Bandingkan dua periode waktu
                $change = $avgRecent - $avgPrevious;
            }

            $book->rating_change_7d = round($change, 4);
            $book->trending_score = ($book->recent_count ?? 0) - (($book->ratings_count ?? 0) / 12); // Re-calculate trending 30d

            return $book;
        });

        // Jika user minta sorting berdasarkan trending, lakukan sorting di PHP
        if ($request->input('sort_by') === 'trending') {
            $sorted = $books->getCollection()->sortByDesc('trending_score')->values();
            $books->setCollection($sorted);
        }

        // Data tambahan
        $categories = Category::select('name')->get()
            ->pluck('name')
            ->map(fn($n) => trim(explode('#', $n)[0]))
            ->unique()
            ->sort()
            ->values();


        $authors = Author::select('id', 'name')->orderBy('name')->get();
        $storeLocations = ['Jakarta', 'Bali', 'Bandung', 'Surabaya'];
        $availabilities = ['available', 'rented', 'reserved'];

        // Status rating user
        $ratedBookIds = Rating::where('user_identifier', $userIdentifier)->pluck('book_id')->toArray();
        $lastRating = Rating::where('user_identifier', $userIdentifier)->latest()->first();
        $within24 = $lastRating && now()->diffInHours($lastRating->created_at) < 24;

        return view('books.index', compact(
            'books',
            'categories',
            'authors',
            'storeLocations',
            'availabilities',
            'ratedBookIds',
            'within24'
        ));
    }
}
