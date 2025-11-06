<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
{
    public function create($bookId = null)
    {
        // Ambil semua author beserta relasi buku mereka
        $authors = Author::with('books')->get();

        // Jika book_id dikirim, ambil buku yang dimaksud
        $selectedBook = $bookId ? Book::with('author')->find($bookId) : null;

        return view('ratings.create', compact('authors', 'selectedBook'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'author_id' => 'required|exists:authors,id',
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|min:1|max:10',
        ]);

        $userIdentifier = $request->ip();

        // Cek waktu rating terakhir user
        $lastRating = Rating::where('user_identifier', $userIdentifier)
            ->orderByDesc('created_at')
            ->first();

        if ($lastRating && now()->diffInHours($lastRating->created_at) < 24) {
            $remaining = 24 - now()->diffInHours($lastRating->created_at);
            return back()->withErrors([
                'rating' => "You must wait {$remaining} more hour(s) before submitting another rating."
            ]);
        }

        // Pastikan buku sesuai dengan author
        $book = Book::where('id', $request->book_id)
            ->where('author_id', $request->author_id)
            ->first();

        if (!$book) {
            return back()->withErrors(['book_id' => 'Book does not belong to this author.']);
        }

        // Simpan rating baru
        Rating::create([
            'book_id' => $book->id,
            'user_identifier' => $userIdentifier,
            'rating' => $request->rating,
        ]);

        return redirect()->route('books.index')->with('success', 'Rating saved successfully!');
    }

}
