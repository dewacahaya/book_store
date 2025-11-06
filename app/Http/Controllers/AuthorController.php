<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Author;
use Carbon\Carbon;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'popularity');
        $perPage = 20;

        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        // --- STEP 1: Hitung agregasi rating per author secara efisien ---
        $aggQuery = DB::table('authors')
            ->join('books', 'authors.id', '=', 'books.author_id')
            ->join('ratings', 'books.id', '=', 'ratings.book_id')
            ->select(
                'authors.id',
                'authors.name',
                DB::raw('COUNT(ratings.id) as total_votes'),
                DB::raw('AVG(ratings.rating) as avg_rating'),
                DB::raw('SUM(CASE WHEN ratings.created_at >= "' . $thirtyDaysAgo . '" THEN 1 ELSE 0 END) as recent_votes'),
                DB::raw('AVG(CASE WHEN ratings.created_at >= "' . $thirtyDaysAgo . '" THEN ratings.rating END) as avg_recent'),
                DB::raw('AVG(CASE WHEN ratings.created_at < "' . $thirtyDaysAgo . '" AND ratings.created_at >= "' . $sixtyDaysAgo . '" THEN ratings.rating END) as avg_previous')
            )
            ->groupBy('authors.id', 'authors.name');

        // --- STEP 2: Filter/sort sesuai tab aktif ---
        switch ($filter) {
            case 'rating':
                $aggQuery->orderByDesc('avg_rating');
                break;

            case 'trending':
                // Gunakan select tambahan agar trending_score bisa diurutkan langsung
                $aggQuery
                    ->selectRaw('((COALESCE(avg_recent, 0) - COALESCE(avg_previous, 0)) * (total_votes * 0.1)) as trending_score')
                    ->orderByDesc('trending_score');
                break;

            case 'popularity':
            default:
                $aggQuery->orderByDesc('total_votes');
                break;
        }

        // --- STEP 3: Pagination ringan (gunakan simplePaginate) ---
        $authors = $aggQuery->simplePaginate($perPage);

        // --- STEP 4: Tambahkan total buku (pakai satu query saja) ---
        $authorIds = collect($authors->items())->pluck('id')->all();
        $bookCounts = DB::table('books')->select('author_id', DB::raw('COUNT(*) as total_books'))->whereIn('author_id', $authorIds)
            ->groupBy('author_id')
            ->pluck('total_books', 'author_id');
        foreach ($authors as $author) {
            $author->total_books = $bookCounts[$author->id] ?? 0;
        }

        return view('authors.index', compact('authors', 'filter'));
    }


    public function profile($id)
    {
        // --- STEP 1: Load data dasar tanpa eager loading berat ---
        $author = Author::findOrFail($id);

        // --- STEP 2: Agregasi cepat untuk statistik global ---
        $ratingsData = DB::table('ratings')
            ->join('books', 'ratings.book_id', '=', 'books.id')
            ->where('books.author_id', $id)
            ->selectRaw('
                COUNT(ratings.id) as total_ratings,
                AVG(ratings.rating) as avg_rating,
                SUM(CASE WHEN ratings.created_at >= ? THEN 1 ELSE 0 END) as recent_votes,
                AVG(CASE WHEN ratings.created_at >= ? THEN ratings.rating END) as avg_recent,
                AVG(CASE WHEN ratings.created_at < ? AND ratings.created_at >= ? THEN ratings.rating END) as avg_previous
            ', [
                now()->subDays(30),
                now()->subDays(30),
                now()->subDays(30),
                now()->subDays(60)
            ])
            ->first();

        $author->total_ratings = $ratingsData->total_ratings ?? 0;
        $author->avg_rating = round($ratingsData->avg_rating ?? 0, 2);

        // --- STEP 3: Hitung total buku ---
        $author->total_books = DB::table('books')->where('author_id', $id)->count();

        // --- STEP 4: Buku terbaik & terburuk ---
        $booksWithAvg = DB::table('books')
            ->leftJoin('ratings', 'books.id', '=', 'ratings.book_id')
            ->select('books.id', 'books.title', DB::raw('AVG(ratings.rating) as avg_rating'), DB::raw('COUNT(ratings.id) as total_votes'))
            ->where('books.author_id', $id)
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('avg_rating')
            ->get();

        $author->best_rated_book = $booksWithAvg->first();
        $author->worst_rated_book = $booksWithAvg->sortBy('avg_rating')->first();

        // --- STEP 5: Trending score ---
        $weight = max(1, $ratingsData->total_ratings * 0.1);
        $author->trending_score = (($ratingsData->avg_recent ?? 0) - ($ratingsData->avg_previous ?? 0)) * $weight;

        return view('authors.profile', compact('author', 'booksWithAvg'));
    }
}
