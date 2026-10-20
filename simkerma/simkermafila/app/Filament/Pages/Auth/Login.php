<?php

namespace App\Filament\Pages\Auth;

use App\Models\MasterProgramStudi;
use App\Models\Privilege;
use App\Models\User;
use App\Models\UserPrivilege;
use App\Models\UserProgramStudi;
use App\Services\PolinemaApiService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('NIM / NIP / Username / Email')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $data = $this->form->getState();
        $loginInput = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $remember = (bool) ($data['remember'] ?? false);

        /** @var PolinemaApiService $apiService */
        $apiService = app(PolinemaApiService::class);

        // 1. Jika SSO Polinema aktif dan terkonfigurasi, coba autentikasi ke API SSO
        if ($apiService->isConfigured()) {
            $ssoResult = $apiService->login($loginInput, $password);

            // Jika akun ditemukan namun status TIDAK AKTIF, tolak login secara tegas
            if (($ssoResult['error_type'] ?? '') === 'inactive') {
                throw ValidationException::withMessages([
                    'data.email' => $ssoResult['message'],
                ]);
            }

            // Jika SSO Berhasil
            if ($ssoResult['success'] && !empty($ssoResult['data'])) {
                $ssoData = $ssoResult['data'];
                $user = $this->syncSsoUser($loginInput, $ssoData, $apiService);

                $currentPanel = Filament::getCurrentOrDefaultPanel();
                if (!$user->canAccessPanel($currentPanel)) {
                    // Jika mahasiswa/dosen login di panel admin, arahkan ke panel user jika berhak
                    if ($currentPanel->getId() === 'admin' && $user->canAccessPanel(Filament::getPanel('user'))) {
                        Filament::auth()->login($user, $remember);
                        session()->regenerate();
                        return new class implements LoginResponse {
                            public function toResponse($request)
                            {
                                return redirect('/user');
                            }
                        };
                    }

                    throw ValidationException::withMessages([
                        'data.email' => 'Akun Anda tidak memiliki izin untuk mengakses panel ini.',
                    ]);
                }

                Filament::auth()->login($user, $remember);
                session()->regenerate();

                return app(LoginResponse::class);
            }
        }

        // 2. Fallback: Cek kredensial lokal (Superadmin / Akun Lokal SIMKERMA)
        $localUser = User::query()
            ->where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if ($localUser && Hash::check($password, $localUser->password)) {
            $currentPanel = Filament::getCurrentOrDefaultPanel();
            if (!$localUser->canAccessPanel($currentPanel)) {
                throw ValidationException::withMessages([
                    'data.email' => 'Akun Anda tidak memiliki izin untuk mengakses panel ini.',
                ]);
            }

            Filament::auth()->login($localUser, $remember);
            session()->regenerate();

            return app(LoginResponse::class);
        }

        // Keduanya gagal
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    protected function syncSsoUser(string $loginInput, array $ssoData, PolinemaApiService $apiService): User
    {
        $username = trim($ssoData['username'] ?? $loginInput);
        $name = trim($ssoData['name'] ?? $loginInput);
        $email = !empty($ssoData['email']) ? trim($ssoData['email']) : ($username . '@polinema.ac.id');
        $privilegeStr = strtolower(trim($ssoData['privilege'] ?? ''));

        // Cari atau buat User
        $user = User::query()
            ->where('username', $username)
            ->orWhere('email', $email)
            ->orWhere('email', $username)
            ->first();

        if (!$user) {
            $user = User::create([
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
            ]);
        } else {
            $updates = [];
            if ($user->name !== $name && !empty($name)) {
                $updates['name'] = $name;
            }
            if (empty($user->username)) {
                $updates['username'] = $username;
            }
            if (!empty($updates)) {
                $user->update($updates);
            }
        }

        // Sinkronisasi Privilege & Prodi
        if ($privilegeStr === 'mahasiswa') {
            $mhsPrivilege = Privilege::firstOrCreate(
                ['nama' => 'Mahasiswa'],
                [
                    'is_admin_panel' => false,
                    'deskripsi' => 'Pengguna Mahasiswa Polinema',
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => false,
                ]
            );

            UserPrivilege::updateOrCreate(
                ['user_id' => $user->id],
                ['privilege_id' => $mhsPrivilege->id]
            );

            // Cek data mahasiswa untuk mapping prodi
            $mhsInfo = $apiService->checkMahasiswaAktif($username) ?? $apiService->getMahasiswa($username);
            if (!empty($mhsInfo['id_prodi'])) {
                $apiProdiId = (string) $mhsInfo['id_prodi'];
                $prodi = MasterProgramStudi::where('integrasi_prodi', $apiProdiId)
                    ->orWhere('nama_prodi', 'LIKE', '%' . ($mhsInfo['namaProdi'] ?? '---') . '%')
                    ->first();

                if ($prodi) {
                    UserProgramStudi::updateOrCreate(
                        ['user_id' => $user->id],
                        ['program_studi_id' => $prodi->id]
                    );

                    if ($prodi->jurusan_id) {
                        $user->update(['jurusan_id' => $prodi->jurusan_id]);
                    }
                }
            }
        } elseif (in_array($privilegeStr, ['dosen', 'tendik'])) {
            $dosenPrivilege = Privilege::firstOrCreate(
                ['nama' => 'Dosen'],
                [
                    'is_admin_panel' => false,
                    'deskripsi' => 'Pengguna Dosen Polinema',
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => false,
                ]
            );

            // Hanya set privilege jika user belum memiliki privilege khusus (misal Admin)
            if (!$user->userPrivilege) {
                UserPrivilege::create([
                    'user_id' => $user->id,
                    'privilege_id' => $dosenPrivilege->id,
                ]);
            }
        }

        return $user;
    }
}
