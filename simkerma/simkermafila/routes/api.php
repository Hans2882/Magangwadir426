<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KerjasamaController;

Route::get('/kerjasama', [KerjasamaController::class, 'index']);
Route::get('/kerjasama/{id}', [KerjasamaController::class, 'show']);