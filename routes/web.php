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

// ── Preprocess tool page
Route::middleware('auth')->group(function () {
    Route::get('/documents/preprocess/tool', [DocumentController::class, 'showPreprocessTool'])->name('preprocess.tool');
});

// ── Document resource
Route::resource('documents', DocumentController::class)->middleware('auth');
