<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(Request $request)
    {
        // Gunakan IP sebagai user identifier untuk rating
        $userIdentifier = $request->ip();

        /** =========================
         * 1️⃣ VARIABEL DASAR
         * ========================= */
        // Mengambil hanya kolom yang dibutuhkan dan mengurutkannya
        $authors = Author::select('id', 'name')->orderBy('name', 'asc')->get();
        $thirtyDaysAgo = now()->subDays(30);
        $sevenDaysAgo = now()->subDays(7);

        /** =========================
         * 2️⃣ SUBQUERY RATING (Efisien)
         * =========================
         * Menggunakan AVG(CASE WHEN...) untuk menghitung rating 7 hari
         * tanpa JOIN tambahan dan meminimalkan hit ke tabel ratings.
         */
        $ratingsSub = DB::table('ratings')
            ->select('book_id')
            ->selectRaw('
                AVG(rating) AS avg_rating,
                COUNT(*) AS cnt,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS recent_count,
                AVG(CASE WHEN created_at >= ? THEN rating END) AS avg_rating_7d,
                AVG(CASE WHEN created_at < ? THEN rating END) AS avg_rating_pre7d
            ', [$thirtyDaysAgo, $sevenDaysAgo, $sevenDaysAgo])
            ->groupBy('book_id');

        /** =========================
         * 3️⃣ QUERY DASAR BUKU + COMPUTED FIELDS
         * ========================= */
        $query = Book::query()
            ->select([
                // Pilih semua kolom dari books, tapi hanya yang diperlukan untuk meminimalkan data transfer
                'books.id',
                'books.title',
                'books.author_id',
                'books.publisher',
                'books.isbn',
                'books.availability',
                'books.publication_year',
                'books.store_location',

                // Computed fields dari Subquery
                DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(r.cnt, 0) as ratings_count'),
                DB::raw('COALESCE(r.recent_count, 0) as recent_count'),
                DB::raw('(COALESCE(r.recent_count, 0) - (COALESCE(r.cnt, 0) / 12)) as trending_score'),
                DB::raw('CASE WHEN COALESCE(r.avg_rating_pre7d, 0) IS NULL OR COALESCE(r.avg_rating_pre7d, 0) = 0 THEN 0
                      ELSE (COALESCE(r.avg_rating_7d, 0) - COALESCE(r.avg_rating_pre7d, 0))
                      END AS rating_change_7d'),
            ])
            ->leftJoinSub($ratingsSub, 'r', 'r.book_id', '=', 'books.id');

        /** =========================
         * 4️⃣ FILTER-FILTER DINAMIS
         * ========================= */
        // 🔸 CATEGORY FILTER (tetap efisien)
        if ($request->filled('categories')) {
            $categoryNames = (array) $request->input('categories');
            $logic = $request->input('category_logic', 'or');

            if ($logic === 'and') {
                foreach ($categoryNames as $name) {
                    $query->whereHas('categories', fn($q) => $q->where('categories.name', 'like', "{$name}%"));
                }
            } else {
                $query->whereHas('categories', fn($q) =>
                    $q->where(fn($q2) =>
                        collect($categoryNames)->each(fn($name) => $q2->orWhere('categories.name', 'like', "{$name}%"))
                    )
                );
            }
        }

        // 🔸 FILTER SIMPLE (langsung di kolom)
        foreach ([
            'author_id' => 'int',
            'availability' => 'string',
            'store_location' => 'string',
        ] as $field => $type) {
            if ($request->filled($field)) {
                $value = $type === 'int' ? intval($request->$field) : $request->$field;
                $query->where("books.$field", $value);
            }
        }

        // 🔸 RANGE FILTER (YEAR, RATING) - Menggunakan when() untuk keterbacaan
        $query->when($request->filled('year_min'), fn($q) => $q->where('books.publication_year', '>=', intval($request->year_min)));
        $query->when($request->filled('year_max'), fn($q) => $q->where('books.publication_year', '<=', intval($request->year_max)));
        $query->when($request->filled('rating_min'), fn($q) => $q->whereRaw('COALESCE(r.avg_rating, 0) >= ?', [floatval($request->rating_min)]));
        $query->when($request->filled('rating_max'), fn($q) => $q->whereRaw('COALESCE(r.avg_rating, 0) <= ?', [floatval($request->rating_max)]));

        // 🔸 KEYWORD
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $like = "%{$keyword}%";
                $q->where('books.title', 'like', $like)
                    ->orWhere('books.isbn', 'like', $like)
                    ->orWhere('books.publisher', 'like', $like)
                    ->orWhereHas('author', fn($qa) => $qa->where('name', 'like', $like));
            });
        }

        /** =========================
         * 5️⃣ SORTING
         * ========================= */
        match ($request->input('sort_by')) {
            'votes' => $query->orderByDesc('ratings_count'),
            'popularity' => $query->orderByDesc('recent_count'),
            'alphabetical' => $query->orderBy('books.title'),
            default => $query->orderByDesc(DB::raw('(COALESCE(r.avg_rating,0) * 0.8 + LEAST(COALESCE(r.cnt,0),50) * 0.01)')),
        };

        /** =========================
         * 6️⃣ PAGINATION + RELASI (Optimal)
         * =========================
         * 1. Paginate query mentah (memuat computed fields).
         * 2. Gunakan loadMissing() pada koleksi paginator untuk memuat relasi (hanya 2 query tambahan).
         * Ini MENCEGAH RE-QUERY model 'Book' yang akan menghilangkan computed fields.
         */
        $perPage = max(5, intval($request->input('per_page', 10)));
        $paginator = $query->simplePaginate($perPage)->appends($request->query());

        // OPTIMASI KRITIS: Eager load relasi langsung ke item yang sudah di-paginate
        $paginator->getCollection()->loadMissing(['author:id,name', 'categories:id,name']);

        /** =========================
         * 7️⃣ TAMBAHAN DATA VIEW
         * ========================= */
        $categories = Category::select('name')->get()
            ->pluck('name')
            ->map(fn($n) => trim(explode('#', $n)[0]))
            ->unique()
            ->sort()
            ->values();

        $storeLocations = ['Jakarta', 'Bali', 'Bandung', 'Surabaya'];
        $availabilities = ['available', 'rented', 'reserved'];

        // Ambil data voting user untuk menampilkan status vote di view
        $ratedBookIds = Rating::where('user_identifier', $userIdentifier)
            ->pluck('book_id')->toArray();

        $lastRating = Rating::where('user_identifier', $userIdentifier)
            ->latest('created_at')
            ->first();
        $within24 = $lastRating && now()->diffInHours($lastRating->created_at) < 24;

        /** =========================
         * 8️⃣ RETURN VIEW
         * ========================= */
        return view('books.index', compact(
            'paginator',
            'categories',
            'storeLocations',
            'availabilities',
            'authors',
            'ratedBookIds',
            'within24'
        ))->with('books', $paginator);
    }
}
