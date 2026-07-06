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
    protected $description = 'Check for Kerjasama expiring in 30 or 60 days and notify admins.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sixtyDays = now()->addDays(60)->format('Y-m-d');
        $thirtyDays = now()->addDays(30)->format('Y-m-d');

        $expiring = Kerjasama::with('mitra')
            ->whereNotNull('tanggal_akhir')
            ->whereIn('tanggal_akhir', [$sixtyDays, $thirtyDays])
            ->get();
        $users = User::all();

        if ($expiring->isEmpty()) {
            $this->info('No Kerjasama expiring in exactly 30 or 60 days today.');
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
