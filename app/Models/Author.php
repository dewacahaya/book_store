<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'bio'];

    /**
     * Relasi ke books (One to Many)
     */
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    /**
     * Relasi ke ratings melalui books (Has Many Through)
     * Author -> Books -> Ratings
     */
    public function ratings()
    {
        return $this->hasManyThrough(
            Rating::class,  // Model tujuan
            Book::class,    // Model perantara
            'author_id',    // FK di table books
            'book_id',      // FK di table ratings
            'id',           // Local key di table authors
            'id'            // Local key di table books
        );
    }

    /**
     * Scope untuk menghitung total votes
     */
    public function scopeWithTotalVotes($query)
    {
        return $query->withCount('ratings as total_votes');
    }

    /**
     * Scope untuk menghitung average rating
     */
    public function scopeWithAverageRating($query)
    {
        return $query->withAvg('ratings as avg_rating', 'rating');
    }

    /**
     * Scope untuk menghitung total books
     */
    public function scopeWithTotalBooks($query)
    {
        return $query->withCount('books as total_books');
    }

    /**
     * Accessor untuk format avg_rating
     */
    public function getFormattedAvgRatingAttribute()
    {
        return round($this->avg_rating ?? 0, 2);
    }
}
