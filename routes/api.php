<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\VideoApiController;

Route::prefix('v1')->group(function () {
    Route::get('/videos', [VideoApiController::class, 'index']);
    Route::get('/videos/{slug}', [VideoApiController::class, 'show']);
    Route::get('/categories', [VideoApiController::class, 'categories']);
    Route::get('/search', [VideoApiController::class, 'search']);
});
