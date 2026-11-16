<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\PolinemaApiService;

class SyncPegawaiUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simkerma:sync-pegawai {identifier? : NIP, username, atau email pegawai (kosongkan untuk default akun Wadir 4)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data akun Dosen / Pegawai / Wadir 4 dari API Kepegawaian Polinema (NIP, Nama Gelar, Jurusan, Prodi, Privilege)';

    /**
     * Execute the console command.
     */
    public function handle(PolinemaApiService $apiService): int
    {
        $identifier = $this->argument('identifier') ?? '198010102005011001';

        $this->info("Menghubungi API Kepegawaian Polinema untuk identifier: {$identifier}...");

        $pegawaiInfo = $apiService->getPegawaiDetail($identifier);

        if (empty($pegawaiInfo)) {
            $this->error("Data pegawai tidak ditemukan di API Kepegawaian Polinema.");
            return Command::FAILURE;
        }

        $biodata = $pegawaiInfo['biodata'] ?? [];
        $identitas = $pegawaiInfo['nomor_identitas'] ?? [];

        $nip = $identitas['nipBaru'] ?? $identitas['nipLama'] ?? $identifier;
        $namaGelar = $biodata['namaGelar'] ?? $biodata['namaPegawai'] ?? '-';
        $email = $biodata['email_institusi'] ?? null;

        $this->info("Data API ditemukan:");
        $this->line(" - NIP        : {$nip}");
        $this->line(" - Nama       : {$namaGelar}");
        $this->line(" - Email      : " . ($email ?? '-'));
        $this->line(" - Penempatan : " . ($biodata['penempatan'] ?? '-'));
        $this->line(" - Homebase   : " . ($biodata['homebase'] ?? '-'));

        // 1. Cari user utama di database:
        // Prioritaskan mencari user berdasarkan identifier yang diketik Gayuh (username SSO atau email)
        $user = User::query()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('email', $identifier . '@polinema.ac.id')
            ->first();

        // 2. Jika belum ditemukan, cari berdasarkan NIP, email Simpeg, atau nama lengkap
        if (!$user) {
            $user = User::query()
                ->where('username', $nip)
                ->when($email, fn ($q) => $q->orWhere('email', $email))
                ->when(!empty($namaGelar), fn ($q) => $q->orWhere('name', $namaGelar))
                ->when(!empty($biodata['namaPegawai']), fn ($q) => $q->orWhere('name', $biodata['namaPegawai']))
                ->first();
        }

        if (!$user) {
            $this->comment("User belum ada di database lokal. Membuat user baru...");
            $user = User::create([
                'username' => $nip,
                'name' => $namaGelar,
                'email' => $email ?? ($nip . '@polinema.ac.id'),
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            ]);
        }

        // 3. Tangani akun duplikat untuk orang yang sama jika ada di database
        $duplicates = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($nip, $namaGelar, $biodata, $identifier, $email) {
                $q->where('username', $nip)
                  ->orWhere('username', $identifier)
                  ->when($email, fn ($sub) => $sub->orWhere('email', $email))
                  ->when(!empty($namaGelar), fn ($sub) => $sub->orWhere('name', $namaGelar))
                  ->when(!empty($biodata['namaPegawai']), fn ($sub) => $sub->orWhere('name', $biodata['namaPegawai']));
            })
            ->get();

        foreach ($duplicates as $dup) {
            $this->warn("Ditemukan akun duplikat untuk orang yang sama: ID {$dup->id} (Username: {$dup->username}, Email: {$dup->email}).");
            $dup->update(['username' => null]);
            $hasProposals = \App\Models\UsulanKerjasama::where('user_id', $dup->id)->exists();
            if (!$hasProposals) {
                $dup->delete();
                $this->info("Akun duplikat kosong (ID {$dup->id}) berhasil dibersihkan otomatis.");
            }
        }

        $user = $apiService->syncPegawaiDataToUser($user, $pegawaiInfo);

        $this->newLine();
        $this->info("=== HASIL SINKRONISASI USER ===");
        $this->line(" - ID User      : {$user->id}");
        $this->line(" - Username     : {$user->username}");
        $this->line(" - Nama         : {$user->name}");
        $this->line(" - Email        : {$user->email}");
        $this->line(" - Jurusan      : " . ($user->jurusan?->nama_jurusan ?? 'NULL'));
        $this->line(" - Program Studi: " . ($user->userProgramStudi?->programStudi?->nama_prodi ?? 'NULL'));
        $this->line(" - Privilege    : " . ($user->userPrivilege?->privilege?->nama ?? 'NULL'));

        $this->newLine();
        $this->info("Sinkronisasi akun pegawai berhasil diselesaikan!");

        return Command::SUCCESS;
    }
}
