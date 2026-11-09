<?php

use Illuminate\Support\Facades\Route;
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
        $parsedHost = parse_url($path, PHP_URL_HOST);
        $allowedHosts = [
            'drive.google.com',
            'docs.google.com',
            'sheets.google.com',
            'storage.googleapis.com',
        ];

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost) {
            $allowedHosts[] = $appHost;
        }

        $isAllowed = false;
        if ($parsedHost) {
            foreach ($allowedHosts as $allowedHost) {
                if ($parsedHost === $allowedHost || str_ends_with($parsedHost, '.' . $allowedHost)) {
                    $isAllowed = true;
                    break;
                }
            }
        }

        if (! $isAllowed) {
            return abort(400, 'Tautan dokumen tidak sah atau domain tidak diizinkan.');
        }

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

        return abort(404, 'Dokumen tidak ditemukan atau sedang tidak dapat diakses di Google Drive.');
    }
})->name('view-dokumen');

Route::get('/calendar/kerjasama', KerjasamaCalendarController::class)
    ->middleware('auth')
    ->name('calendar.kerjasama');

Route::get('/api/mitra-docs', function () {
    return view('api.mitra');
})->name('api.mitra.docs');