<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/view-dokumen', function (\Illuminate\Http\Request $request) {
    $path = $request->query('path');
    if (!$path || $path === '-') {
        return abort(404);
    }
    
    if (str_starts_with($path, 'http')) {
        return redirect($path);
    }

    try {
        $url = \Illuminate\Support\Facades\Storage::disk('google')->url($path);
        preg_match('/id=([^&]+)/', $url, $matches);
        $finalUrl = isset($matches[1]) ? "https://drive.google.com/file/d/{$matches[1]}/view" : $url;
        return redirect($finalUrl);
    } catch (\Exception $e) {
        return abort(404, 'File tidak ditemukan di Google Drive.');
    }
})->name('view-dokumen');
