<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are automatically prefixed with /api
| Middleware: api (stateless, no session/CSRF)
|
*/

// Health check
Route::get('/ai-health', [DocumentController::class, 'checkAiHealth']);

// Text preprocessing
Route::post('/preprocess', [DocumentController::class, 'preprocessText']);

// Document preprocessing (GET and POST for frontend compatibility)
Route::match(['GET', 'POST'], '/preprocess/document/{id}', [DocumentController::class, 'preprocessDocument']);

// Document search
Route::post('/search', [DocumentController::class, 'searchDocuments']);

// Document analysis
Route::post('/analyze-document', [DocumentController::class, 'analyzeDocument']);

// Query analysis
Route::post('/analyze-query', [DocumentController::class, 'analyzeQuery']);
