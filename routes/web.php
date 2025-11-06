<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BookController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\RatingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::post('/books/{book}/rate', [BookController::class, 'rate'])->name('books.rate');

Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('/authors/{author}/profile', [AuthorController::class, 'profile'])->name('authors.profile');

Route::get('/ratings/create/{book}', [RatingController::class, 'create'])->name('ratings.create');
Route::post('/ratings', [RatingController::class, 'store'])->name('ratings.store');

