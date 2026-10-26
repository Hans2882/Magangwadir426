<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kerjasama;
use App\Models\User;
use Filament\Notifications\Notification;

class CheckExpiringKerjasama extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-expiring-kerjasama';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for Kerjasama expiring in exactly 4 months and notify users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fourMonths = now()->addMonths(4)->format('Y-m-d');
        $threeMonths = now()->addMonths(3)->format('Y-m-d');

        $expiring = Kerjasama::with('mitra')
            ->whereNotNull('tanggal_akhir')
            ->whereIn('tanggal_akhir', [$fourMonths, $threeMonths])
            ->get();

        // Targetkan staf/pimpinan/admin (bukan mahasiswa umum)
        $users = User::whereHas('userPrivilege.privilege', function ($q) {
            $q->where('is_admin_panel', true)
              ->orWhereIn('nama', ['Wadir 4', 'Wakil Direktur IV', 'Koor Magang', 'Koordinator Magang', 'Dosen']);
        })->get();

        if ($users->isEmpty()) {
            $users = User::all();
        }

        if ($expiring->isEmpty()) {
            $this->info('No Kerjasama expiring in 3 or 4 months today.');
            return;
        }

        foreach ($expiring as $item) {
            $days = now()->startOfDay()->diffInDays($item->tanggal_akhir);
            
            $mitraName = $item->mitra ? $item->mitra->nama_mitra : 'Unknown Mitra';
            
            Notification::make()
                ->title('Kerjasama Segera Berakhir')
                ->body("{$item->judul} Dengan {$mitraName} Berakhir dalam {$days} hari.")
                ->warning()
                ->sendToDatabase($users);
        }

        $this->info("Sent {$expiring->count()} notifications to {$users->count()} users.");
    }
}
