<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'jurusan_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userPrivilege(): HasOne
{
    return $this->hasOne(UserPrivilege::class);
}

public function userProgramStudi(): HasOne
{
    return $this->hasOne(UserProgramStudi::class);
}

public function jurusan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(MasterJurusan::class, 'jurusan_id');
}

public function getFilamentName(): string
{
    $prodi = $this->userProgramStudi?->programStudi?->nama_prodi;
    $jurusan = $this->jurusan?->nama_jurusan;
    
    $context = array_filter([$prodi, $jurusan]);
    
    if (!empty($context)) {
        return $this->name . ' (' . implode(' - ', $context) . ')';
    }

    return $this->name;
}

    public function isMahasiswa(): bool
    {
        return strcasecmp($this->userPrivilege?->privilege?->nama ?? '', 'Mahasiswa') === 0;
    }

    public function isDosen(): bool
    {
        return strcasecmp($this->userPrivilege?->privilege?->nama ?? '', 'Dosen') === 0;
    }

    public function isWadir4(): bool
    {
        return strcasecmp($this->userPrivilege?->privilege?->nama ?? '', 'Wakil Direktur IV') === 0;
    }

    public function isKoorMagang(): bool
    {
        return strcasecmp($this->userPrivilege?->privilege?->nama ?? '', 'Koor Magang') === 0;
    }

    public function getProdiId(): ?int
    {
        return $this->userProgramStudi?->program_studi_id;
    }

    public function getJurusanId(): ?int
    {
        return $this->jurusan_id ?? $this->userProgramStudi?->programStudi?->jurusan_id;
    }

    public static function getWadir4(): object
    {
        $user = static::whereHas('userPrivilege.privilege', function ($query) {
            $query->where('nama', 'Wakil Direktur IV');
        })->first();

        return (object) [
            'nama' => $user?->name ?? 'Prof. Dr. Eng. Rosa Andrie Asmara, S.T., M.T.',
            'nip'  => $user?->username ?? '198010102005011001',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $isAdmin = $this->userPrivilege?->privilege?->is_admin_panel ?? false;
        
        if ($panel->getId() === 'admin') {
            return $isAdmin;
        }

        if ($panel->getId() === 'user') {
            return true; // All authenticated users can access the user panel
        }

        return false;
    }
}
