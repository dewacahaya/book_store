@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>📊Authors Ranking</h3>

        <form method="GET" action="{{ route('authors.index') }}">
            <select name="filter" onchange="this.form.submit()" class="form-select w-auto">
                <option value="popularity" {{ $filter === 'popularity' ? 'selected' : '' }}>By Popularity</option>
                <option value="rating" {{ $filter === 'rating' ? 'selected' : '' }}>By Average Rating</option>
                <option value="trending" {{ $filter === 'trending' ? 'selected' : '' }}>Trending</option>
            </select>
        </form>
    </div>

    <table class="table table-bordered table-striped bg-white">
        <thead class="table-primary">
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>Total Books</th>
                @if ($filter === 'popularity')
                    <th>Total Votes</th>
                @elseif ($filter === 'rating')
                    <th>Average Rating</th>
                @elseif ($filter === 'trending')
                    <th>Trending Score</th>
                @endif
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($authors as $author)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $author->name }}</td>
                    <td>{{ $author->total_books ?? '-' }}</td>

                    @if ($filter === 'popularity')
                        <td>{{ $author->total_votes ?? 0 }}</td>
                    @elseif ($filter === 'rating')
                        <td>{{ number_format($author->avg_rating ?? 0, 2) }}</td>
                    @elseif ($filter === 'trending')
                        <td>{{ number_format($author->trending_score ?? 0, 2) }}</td>
                    @endif

                    <td>
                        <a href="{{ route('authors.profile', $author->id) }}" class="btn btn-sm btn-outline-primary">
                            View Profile
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $authors->links() }}
</div>
@endsection
