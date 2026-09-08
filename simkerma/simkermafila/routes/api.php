<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KerjasamaController;
use App\Http\Controllers\Api\MitraController;

Route::middleware('api.key')->group(function () {
    Route::get('/mitra', [MitraController::class, 'index'])
        ->name('api.mitra');

    Route::get('/mou', [KerjasamaController::class, 'mou'])
        ->name('api.mou');

    Route::get('/moa', [KerjasamaController::class, 'moa'])
        ->name('api.moa');

    Route::get('/ia', [KerjasamaController::class, 'ia'])
        ->name('api.ia');

    Route::get('/pks', [KerjasamaController::class, 'pks'])
        ->name('api.pks');
});