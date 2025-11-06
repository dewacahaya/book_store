@extends('layouts.app')

@section('content')
    <h2 class="mb-4 fw-bold">📖 Book List</h2>

    <div class="card mb-4 p-3">
        <form action="{{ route('books.index') }}" method="get">
            <div class="row g-3 align-items-end">

                {{-- Keyword --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Title, author, ISBN, publisher"
                        value="{{ request('keyword') }}">
                </div>

                {{-- Author --}}
                <div class="col-md-3">
                    <label for="author" class="form-label">Author</label>
                    <select name="author_id" id="author" class="form-select">
                        <option value="{{ request('author') }}">-- Select Author --</option>
                        @foreach ($authors as $author)
                            <option value="{{ $author->id }}" {{ request('author_id') == $author->id ? 'selected' : '' }}>
                                {{ $author->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Categories --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Categories</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($categories as $catName)
                            <div class="form-check me-3">
                                <input type="checkbox" name="categories[]" id="cat_{{ $loop->index }}"
                                    value="{{ $catName }}" class="form-check-input"
                                    {{ collect(request('categories'))->contains($catName) ? 'checked' : '' }}>
                                <label class="form-check-label" for="cat_{{ $loop->index }}">
                                    {{ $catName }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Category Logic --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Category Logic</label>
                    <select name="category_logic" class="form-select">
                        <option value="or" {{ request('category_logic') == 'or' ? 'selected' : '' }}>OR (any match)
                        </option>
                        <option value="and" {{ request('category_logic') == 'and' ? 'selected' : '' }}>AND (all match)
                        </option>
                    </select>
                </div>

                {{-- Year Range --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Year (Min)</label>
                    <input type="number" name="year_min" class="form-control" value="{{ request('year_min') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Year (Max)</label>
                    <input type="number" name="year_max" class="form-control" value="{{ request('year_max') }}">
                </div>

                {{-- Availability --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Availability</label>
                    <select name="availability" class="form-select">
                        <option value="">All</option>
                        @foreach ($availabilities as $status)
                            <option value="{{ $status }}"
                                {{ request('availability') == $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Store Location --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Store Location</label>
                    <select name="store_location" class="form-select">
                        <option value="">All</option>
                        @foreach ($storeLocations as $loc)
                            <option value="{{ $loc }}" {{ request('store_location') == $loc ? 'selected' : '' }}>
                                {{ $loc }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rating Range --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Rating Min</label>
                    <input type="number" name="rating_min" class="form-control" min="1" max="10"
                        value="{{ request('rating_min') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Rating Max</label>
                    <input type="number" name="rating_max" class="form-control" min="1" max="10"
                        value="{{ request('rating_max') }}">
                </div>

                {{-- Sort --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort By</label>
                    <select name="sort_by" class="form-select">
                        <option value="">Weighted Rating (default)</option>
                        <option value="votes" {{ request('sort_by') == 'votes' ? 'selected' : '' }}>Total Votes</option>
                        <option value="popularity" {{ request('sort_by') == 'popularity' ? 'selected' : '' }}>Recent
                            Popularity (30d)</option>
                        <option value="alphabetical" {{ request('sort_by') == 'alphabetical' ? 'selected' : '' }}>
                            Alphabetical</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Filter</button>
                    <a href="{{ route('books.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <table class="table table-hover table-bordered bg-white">
        <thead class="table-primary">
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Categories</th>
                <th>ISBN</th>
                <th>Publisher</th>
                <th>Publication Year</th>
                <th>Store Location</th>
                <th>Average Rating</th>
                <th>Total Voters</th>
                <th>Status</th>
                <th>Vote</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($books as $book)
                <tr>
                    <td class="w-25">
                        @if (isset($book->rating_change_7d) && $book->rating_change_7d > 0.0001)
                            <span class="text-success fw-bold me-1"
                                title="Rating naik {{ number_format($book->rating_change_7d, 2) }} poin dalam 7 hari">
                                ▲
                            </span>
                        @elseif (isset($book->rating_change_7d) && $book->rating_change_7d < -0.0001)
                            <span class="text-danger fw-bold me-1"
                                title="Rating turun {{ number_format(abs($book->rating_change_7d), 2) }} poin dalam 7 hari">
                                ▼
                            </span>
                        @else
                            <span class="text-secondary me-1" title="Rating stabil/perubahan tidak signifikan dalam 7 hari">
                                -
                            </span>
                        @endif
                        {{ $book->title }}
                    </td>
                    <td>{{ $book->author->name ?? '-' }}</td>
                    <td>
                        @foreach ($book->categories as $cat)
                            <span class="badge text-bg-secondary">{{ $cat->name }}</span>
                        @endforeach
                    </td>
                    <td>{{ $book->isbn }}</td>
                    <td>{{ $book->publisher ?? '-' }}</td>
                    <td>{{ $book->publication_year }}</td>
                    <td>{{ $book->store_location }}</td>
                    <td>{{ number_format($book->ratings->avg('rating') ?? 0, 1) }}</td>
                    <td>{{ $book->ratings->count() }}</td>
                    <td>
                        <span
                            class="badge
                            @if ($book->availability == 'available') bg-success
                            @elseif($book->availability == 'rented') bg-warning
                            @else bg-secondary @endif">
                            {{ ucfirst($book->availability) }}
                        </span>
                    </td>
                    <td>
                        @if (in_array($book->id, $ratedBookIds))
                            <button class="btn btn-secondary" disabled>Voted</button>
                        @elseif ($within24)
                            <button class="btn btn-outline-secondary alert-btn">Vote</button>
                        @else
                            <a href="{{ route('ratings.create', $book->id) }}" class="btn btn-primary">Vote</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No books found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center mt-3">
        {{ $books->links() }}
    </div>

    <script>
        document.querySelectorAll('.alert-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                alert('You can only vote once every 24 hours. Please try again later.');
            });
        });
    </script>
@endsection
