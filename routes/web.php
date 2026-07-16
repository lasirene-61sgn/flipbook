<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;

Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::post('/upload', [BookController::class, 'store'])->name('books.store');
Route::get('/book/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/book/{book}/stream', [BookController::class, 'stream'])->name('books.stream');
// Handle CORS preflight for the stream endpoint (required for ngrok + cross-origin pdf.js Range requests)
Route::options('/book/{book}/stream', function () {
    return response('', 204, [
        'Access-Control-Allow-Origin'   => '*',
        'Access-Control-Allow-Methods'  => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers'  => 'Range, Content-Type, ngrok-skip-browser-warning, Accept',
        'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
        'Access-Control-Max-Age'        => '86400',
    ]);
});