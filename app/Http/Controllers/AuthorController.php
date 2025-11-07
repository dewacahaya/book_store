<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Author;
use Carbon\Carbon;

class AuthorController extends Controller
{
    /**
     * Hitung trending score berdasarkan selisih rata-rata
     * dengan bobot total votes (skala logaritmik sederhana)
     */
    private function calculateTrendingScore($avgRecent, $avgPrev, $votes)
    {
        $weight = max(1, $votes * 0.1); // bobot stabil
        return round(($avgRecent - $avgPrev) * $weight, 4);
    }

    /**
     * Query base author dengan semua metrik (reusable)
     */
    private function baseAuthorQuery()
    {
        $thirtyDaysAgo = now()->subDays(30);
        $sixtyDaysAgo = now()->subDays(60);

        return Author::query()
            ->withCount(['books', 'ratings as total_votes'])
            ->withAvg('ratings as avg_rating', 'rating')
            ->withAvg([
                'ratings as avg_recent' => fn($q) =>
                    $q->where('ratings.created_at', '>=', $thirtyDaysAgo)
            ], 'rating')
            ->withAvg([
                'ratings as avg_previous' => fn($q) =>
                    $q->whereBetween('ratings.created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
            ], 'rating');
    }

    /**
     * Tampilkan daftar author (index page)
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'popularity');
        $perPage = 20;

        $q = $this->baseAuthorQuery();

        // Urutkan berdasarkan filter
        $q->when($filter === 'rating', fn($q) => $q->orderByDesc('avg_rating'))
            ->when($filter === 'popularity', fn($q) => $q->orderByDesc('total_votes'));

        $authors = $q->simplePaginate($perPage)->appends($request->query());

        // Hitung nilai tambahan
        $authors->getCollection()->transform(function ($author) {
            $votes = (int) ($author->total_votes ?? 0);
            $avgRecent = $author->avg_recent ?? 0;
            $avgPrev = $author->avg_previous ?? 0;

            $author->trending_score = $this->calculateTrendingScore($avgRecent, $avgPrev, $votes);
            $author->avg_rating = round($author->avg_rating ?? 0, 2);
            $author->total_books = $author->books_count ?? 0;

            return $author;
        });

        return view('authors.index', compact('authors', 'filter'));
    }

    /**
     * Detail profil author (profile page)
     */
    public function profile($id)
    {
        $author = $this->baseAuthorQuery()
            ->findOrFail($id);

        $votes = (int) ($author->total_votes ?? 0);
        $avgRecent = $author->avg_recent ?? 0;
        $avgPrev = $author->avg_previous ?? 0;

        $author->trending_score = $this->calculateTrendingScore($avgRecent, $avgPrev, $votes);
        $author->avg_rating = round($author->avg_rating ?? 0, 2);
        $author->total_books = $author->books_count ?? 0;
        $author->total_ratings = $votes;

        // Ambil daftar buku beserta statistik rating
        $books = $author->books()
            ->withAvg('ratings as avg_rating', 'rating')
            ->withCount('ratings as total_votes')
            ->get();

        $author->best_rated_book = $books->where('total_votes', '>', 0)->sortByDesc('avg_rating')->first();
        $author->worst_rated_book = $books->where('total_votes', '>', 0)->sortBy('avg_rating')->first();

        return view('authors.profile', compact('author', 'books'));
    }
}
