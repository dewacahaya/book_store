<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RatingController extends Controller
{
    /**
     * Tampilkan form input rating.
     */
    public function create($bookId = null)
    {
        // Ambil semua author beserta buku mereka
        $authors = Author::with('books:id,author_id,title')->orderBy('name')->get();

        // Jika user memilih buku, muat juga relasi author
        $selectedBook = $bookId
            ? Book::with('author:id,name')->findOrFail($bookId)
            : null;

        return view('ratings.create', compact('authors', 'selectedBook'));
    }

    /**
     * Simpan rating buku baru.
     */
    public function store(Request $request)
    {
        $userIdentifier = $request->ip(); // gunakan IP address sbg identitas sederhana

        // Validasi input
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|min:1|max:10',
        ]);

        DB::beginTransaction();
        try {
            // ✅ Cegah rating lebih dari 1x per 24 jam
            $lastRating = Rating::where('user_identifier', $userIdentifier)
                ->latest('created_at')
                ->first();

            if ($lastRating && now()->diffInHours($lastRating->created_at) < 24) {
                DB::rollBack();
                return redirect()->back()
                    ->withErrors(['wait' => 'Anda hanya dapat memberi rating sekali setiap 24 jam.'])
                    ->withInput();
            }

            // ✅ Cegah rating duplikat untuk buku yg sama
            $duplicate = Rating::where('book_id', $validated['book_id'])
                ->where('user_identifier', $userIdentifier)
                ->exists();

            if ($duplicate) {
                DB::rollBack();
                return redirect()->back()
                    ->withErrors(['duplicate' => 'Anda sudah pernah memberi rating untuk buku ini.'])
                    ->withInput();
            }

            // ✅ Simpan rating baru via relasi Eloquent
            $book = Book::findOrFail($validated['book_id']);
            $book->ratings()->create([
                'rating' => $validated['rating'],
                'user_identifier' => $userIdentifier,
            ]);

            DB::commit();

            return redirect()
                ->route('books.index')
                ->with('success', 'Rating berhasil dikirim!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan rating: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan rating.'])
                ->withInput();
        }
    }
}
