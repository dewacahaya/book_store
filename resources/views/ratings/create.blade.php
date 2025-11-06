@extends('layouts.app')

@section('content')
    <h2 class="mb-4 fw-bold">⭐ Give a Rating</h2>

    <form action="{{ route('ratings.store') }}" method="POST" class="card p-4 bg-white shadow-sm">
        @csrf

        <div class="mb-3">
            <label for="author" class="form-label">Author</label>
            <select name="author_id" id="author" class="form-select" required>
                <option value="">-- Select Author --</option>
                @foreach ($authors as $author)
                    <option value="{{ $author->id }}"
                        {{ isset($selectedBook) && $selectedBook->author_id == $author->id ? 'selected' : '' }}>
                        {{ $author->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="book" class="form-label">Book (related to author)</label>
            <select name="book_id" id="book" class="form-select" required>
                <option value="">-- Select Book --</option>
                @if (isset($selectedBook))
                    <option value="{{ $selectedBook->id }}" selected>{{ $selectedBook->title }}</option>
                @endif
            </select>
        </div>

        <div class="mb-3">
            <label for="rating" class="form-label">Rating (1–10)</label>
            <select name="rating" id="rating" class="form-select" required>
                @for ($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Submit Rating</button>
    </form>

    <script>
        const authors = @json($authors);
        const authorSelect = document.getElementById('author');
        const bookSelect = document.getElementById('book');
        const selectedBookId = {{ isset($selectedBook) ? $selectedBook->id : 'null' }};

        function populateBooks(authorId) {
            bookSelect.innerHTML = '<option value="">-- Select Book --</option>';
            const author = authors.find(a => a.id == authorId);
            if (author && author.books.length > 0) {
                author.books.forEach(book => {
                    const option = document.createElement('option');
                    option.value = book.id;
                    option.textContent = book.title;
                    if (selectedBookId && book.id === selectedBookId) {
                        option.selected = true;
                    }
                    bookSelect.appendChild(option);
                });
            }
        }

        authorSelect.addEventListener('change', e => populateBooks(e.target.value));

        // Auto-load jika ada selectedBook
        if (authorSelect.value) populateBooks(authorSelect.value);
    </script>
@endsection
