<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/upload', function () {
    return view('upload');
});
use App\Http\Controllers\DocumentController;

Route::get('/upload', function () {
    return view('upload');
});

// POST route for form submission
Route::post('/upload', [DocumentController::class, 'store'])->name('documents.store');