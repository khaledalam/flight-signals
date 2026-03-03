<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::get('/docs', fn () => view('docs'));

Route::get('/openapi.json', fn () => response()->file(base_path('openapi/openapi.json'), [
    'Content-Type' => 'application/json',
]));

Route::get('/admin', [AdminController::class, 'index'])->middleware('auth.basic.admin');
