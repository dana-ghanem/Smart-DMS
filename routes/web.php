<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AuthController;

Route::resource('documents', DocumentController::class)->middleware('auth');

// Text Preprocessing & AI Search API Routes
Route::middleware('auth')->group(function () {
    Route::get('/documents/preprocess/tool', [DocumentController::class, 'showPreprocessTool'])->name('preprocess.tool');
    Route::post('/api/preprocess', [DocumentController::class, 'preprocessText'])->name('preprocess.text');
    Route::post('/api/analyze-document', [DocumentController::class, 'analyzeDocument'])->name('analyze.document');

    // AI Search Routes
    Route::post('/api/search', [DocumentController::class, 'searchDocuments'])->name('search.documents');
    Route::post('/api/analyze-query', [DocumentController::class, 'analyzeQuery'])->name('analyze.query');
});

// Home
Route::get('/', function () {
    return view('welcome');
});

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
