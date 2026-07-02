<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MitraController;

Route::get('/', function () {
    return view('dashboard');
});

// Data Mitra
Route::get('/data-mitra/kategori-mitra', function () {
    return view('data-mitra.kategori-mitra');
})->name('data-mitra.kategori-mitra');

Route::get('/data-mitra/data-mitra',              [MitraController::class, 'index'])->name('data-mitra.data-mitra');
Route::get('/data-mitra/ajax/dalam-negeri',       [MitraController::class, 'dataDalamNegeri'])->name('data-mitra.ajax.dalam-negeri');
Route::get('/data-mitra/ajax/luar-negeri',        [MitraController::class, 'dataLuarNegeri'])->name('data-mitra.ajax.luar-negeri');


use App\Http\Controllers\DataMouController;

// Data Kerjasama – MoU
Route::get('/data-kerjasama/data-mou',             [DataMouController::class, 'index'])->name('data-kerjasama.data-mou');
Route::get('/data-kerjasama/ajax/mou-dalam-negeri',[DataMouController::class, 'dataDalamNegeri'])->name('data-kerjasama.ajax.mou-dalam-negeri');
Route::get('/data-kerjasama/ajax/mou-luar-negeri', [DataMouController::class, 'dataLuarNegeri'])->name('data-kerjasama.ajax.mou-luar-negeri');

use App\Http\Controllers\KerjasamaController;

// Data Kerjasama – PKS (no LN records)
Route::get('/data-kerjasama/data-pks',             [KerjasamaController::class, 'pksIndex'])->name('data-kerjasama.data-pks');
Route::get('/data-kerjasama/ajax/pks-dalam-negeri',[KerjasamaController::class, 'pksDN'])->name('data-kerjasama.ajax.pks-dalam-negeri');

// Data Kerjasama – IA
Route::get('/data-kerjasama/data-ia',              [KerjasamaController::class, 'iaIndex'])->name('data-kerjasama.data-ia');
Route::get('/data-kerjasama/ajax/ia-dalam-negeri', [KerjasamaController::class, 'iaDN'])->name('data-kerjasama.ajax.ia-dalam-negeri');
Route::get('/data-kerjasama/ajax/ia-luar-negeri',  [KerjasamaController::class, 'iaLN'])->name('data-kerjasama.ajax.ia-luar-negeri');

// Simmagang
Route::get('/simmagang/permintaan-kerjasama', function () {
    return view('simmagang.permintaan-kerjasama');
})->name('simmagang.permintaan-kerjasama');
