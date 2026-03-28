<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

Route::resource('documents', DocumentController::class);

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-docs', function() {
    $documents = App\Models\Document::all();
    return view('documents.test', compact('documents'));
});