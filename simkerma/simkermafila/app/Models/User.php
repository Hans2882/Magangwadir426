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
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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

public function getFilamentName(): string
{
    $prodi = $this->userProgramStudi?->programStudi?->nama_prodi;
    
    if ($prodi) {
        return "{$this->name} - {$prodi}";
    }

    return $this->name;
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
