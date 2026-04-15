<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AuthController;

// ── Home
Route::get('/', function () {
    return view('welcome');
});

// ── Auth
Route::get('/login',     [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login',    [AuthController::class, 'login']);
Route::get('/register',  [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout',   [AuthController::class, 'logout'])->name('logout');

// ── REST API
Route::middleware('auth:web')->prefix('ui-api')->group(function () {
    Route::post('/preprocess',       [DocumentController::class, 'preprocessText'])->name('preprocess.text');
    Route::get('/preprocess/document/{id}', [DocumentController::class, 'preprocessDocument'])->name('preprocess.document');
    Route::post('/search',           [DocumentController::class, 'searchDocuments'])->name('search.documents');
    Route::post('/analyze-document', [DocumentController::class, 'analyzeDocument'])->name('analyze.document');
    Route::post('/analyze-query',    [DocumentController::class, 'analyzeQuery'])->name('analyze.query');
    Route::get('/ai-health',         [DocumentController::class, 'checkAiHealth'])->name('ai.health');
});

// ── Preprocess tool page
Route::middleware('auth')->group(function () {
    Route::get('/documents/preprocess/tool', [DocumentController::class, 'showPreprocessTool'])->name('preprocess.tool');
});

// ── Document resource
Route::resource('documents', DocumentController::class)->middleware('auth');
