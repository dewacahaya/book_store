<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookStore Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 position-sticky top-0 z-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('books.index') }}">📚 BookStore</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item fw-semibold"><a class="nav-link" href="{{ route('books.index') }}">Books</a></li>
                    <li class="nav-item fw-semibold"><a class="nav-link" href="{{ route('authors.index') }}">Top Authors</a></li>
                    {{-- <li class="nav-item fw-semibold"><a class="nav-link" href="{{ route('ratings.create') }}">Give Rating</a></li> --}}
                </ul>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <div class="container mb-5">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
