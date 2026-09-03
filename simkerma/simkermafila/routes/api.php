<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MitraController;

Route::middleware('api.key')->group(function () {
    Route::get('/mitra', [MitraController::class, 'index'])
        ->name('api.mitra');
});