<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [ApiController::class, 'register']);
    Route::post('/login',    [ApiController::class, 'login']);
});

Route::get('/ai-health', [ApiController::class, 'aiHealth']);

// ── Public preprocess routes (called by browser session)
Route::get('/preprocess/document/{id}',  [ApiController::class, 'preprocessDocument']);
Route::post('/preprocess/document/{id}', [ApiController::class, 'preprocessDocument']);

// ── Protected — Bearer token required (Postman)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [ApiController::class, 'logout']);

    Route::get('/documents',       [ApiController::class, 'listDocuments']);
    Route::get('/documents/{id}',  [ApiController::class, 'getDocument'])->whereNumber('id');
    Route::post('/documents',      [ApiController::class, 'createDocument']);
    Route::put('/documents/{id}',  [ApiController::class, 'updateDocument'])->whereNumber('id');
    Route::delete('/documents/{id}', [ApiController::class, 'deleteDocument'])->whereNumber('id');

    Route::post('/upload',                [ApiController::class, 'uploadDocument']);
    Route::post('/preprocess',            [ApiController::class, 'preprocessText']);
    Route::post('/search',                [ApiController::class, 'searchDocuments']);
    Route::post('/analyze-document',      [ApiController::class, 'analyzeDocument']);
    Route::post('/analyze-query',         [ApiController::class, 'analyzeQuery']);
});
