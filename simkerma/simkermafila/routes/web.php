<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MitraController;
use App\Http\Controllers\KerjasamaCalendarController;

Route::get('/', \App\Livewire\Landing\LandingPage::class)->name('home');

Route::get('/login', function () {
    return redirect(route('filament.admin.auth.login'));
})->name('login');

Route::middleware(['auth'])->get('/view-dokumen', function (\Illuminate\Http\Request $request) {
    $path = $request->query('path');

    if (! $path || $path === '-') {
        return abort(404);
    }

    if ($request->user()?->isMahasiswa() && (str_contains($path, 'StudyCase') || str_contains($path, 'Case_Study'))) {
        return abort(403, 'Anda tidak memiliki hak akses untuk mengunduh atau melihat dokumen ini.');
    }

    if (str_starts_with($path, 'http')) {
        return redirect($path);
    }

    try {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = \Illuminate\Support\Facades\Storage::disk('google');

        // Flysystem Google Drive adapter converts '&' to '_'
        $pathNormalized = str_replace('&', '_', $path);
        $url            = $disk->url($pathNormalized);
        preg_match('/id=([^&]+)/', $url, $matches);
        $finalUrl = isset($matches[1]) ? "https://drive.google.com/file/d/{$matches[1]}/view" : $url;

        return redirect($finalUrl);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Google Drive View Error: ' . $e->getMessage(), ['path' => $path]);

        return abort(404, 'File tidak ditemukan di Google Drive. Detail: ' . $e->getMessage());
    }
})->name('view-dokumen');

Route::get('/calendar/kerjasama', KerjasamaCalendarController::class)
    ->middleware('auth')
    ->name('calendar.kerjasama');

Route::get('/api/mitra-docs', function () {
    return view('api.mitra');
})->name('api.mitra.docs');

Route::get('/api/mitra', [MitraController::class, 'index'])
    ->name('api.mitra');