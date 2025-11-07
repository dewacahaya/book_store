@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>{{ $author->name }}</h2>
                <a href="{{ route('authors.index') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
            </div>

            <p class="text-muted">{{ $author->bio ?? 'No biography available.' }}</p>

            <hr>

            <div class="row text-center">
                <div class="col-md-3">
                    <h5>Total Books</h5>
                    <p class="fw-bold">{{ $author->total_books }}</p>
                </div>
                <div class="col-md-3">
                    <h5>Total Ratings</h5>
                    <p class="fw-bold">{{ $author->total_votes }}</p>
                </div>
                <div class="col-md-3">
                    <h5>Average Rating</h5>
                    <p class="fw-bold text-primary">{{ number_format($author->avg_rating, 2) }}</p>
                </div>
                <div class="col-md-3">
                    <h5>Trending Score</h5>
                    <p class="fw-bold text-success">{{ number_format($author->trending_score, 2) }}</p>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-success">⭐ Best Rated Book</h5>
                            @if ($author->best_rated_book)
                                <p class="fw-bold mb-1">{{ $author->best_rated_book->title }}</p>
                                <p>Rating: {{ number_format($author->best_rated_book->avg_rating, 2) }}</p>
                            @else
                                <p class="text-muted">No ratings yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-danger">💢 Worst Rated Book</h5>
                            @if ($author->worst_rated_book)
                                <p class="fw-bold mb-1">{{ $author->worst_rated_book->title }}</p>
                                <p>Rating: {{ number_format($author->worst_rated_book->avg_rating, 2) }}</p>
                            @else
                                <p class="text-muted">No ratings yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <h4 class="mt-4 mb-3">📚 All Books</h4>
            <table class="table table-striped">
                <thead class="table-secondary">
                    <tr>
                        <th>Title</th>
                        <th>Avg Rating</th>
                        <th>Total Votes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($books as $book)
                        <tr>
                            <td>{{ $book->title }}</td>
                            <td>{{ number_format($book->avg_rating, 2) }}</td>
                            <td>{{ $book->total_votes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
