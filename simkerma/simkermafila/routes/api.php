<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MitraController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API Routes dengan API Key Authentication
Route::middleware('validate.api.key')
    ->prefix('v1')
    ->group(function () {

        Route::get('/mitra', [MitraController::class, 'index'])
            ->name('api.mitra.index');

        Route::get('/mitra/{id}', [MitraController::class, 'show'])
            ->name('api.mitra.show');

        Route::get('/mitra/{id}/kerjasama', [MitraController::class, 'getKerjasama'])
            ->name('api.mitra.kerjasama');

        Route::get('/api-key/info', [MitraController::class, 'getApiKeyInfo'])
            ->name('api.key.info');
    });
