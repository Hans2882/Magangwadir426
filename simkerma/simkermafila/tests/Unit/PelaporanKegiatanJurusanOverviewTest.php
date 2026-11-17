<?php

namespace Tests\Unit;

use App\Filament\Resources\PelaporanKegiatanResource\Widgets\PelaporanKegiatanJurusanOverview;
use App\Models\Privilege;
use App\Models\User;
use App\Models\UserPrivilege;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PelaporanKegiatanJurusanOverviewTest extends TestCase
{
    #[Test]
    public function it_denies_access_to_unauthenticated_users(): void
    {
        Auth::logout();
        $this->assertFalse(PelaporanKegiatanJurusanOverview::canView());
    }

    #[Test]
    public function it_denies_access_to_mahasiswa(): void
    {
        $user = new User(['name' => 'Mahasiswa Test']);
        $privilege = new Privilege(['nama' => 'Mahasiswa']);
        $userPrivilege = new UserPrivilege();
        $userPrivilege->setRelation('privilege', $privilege);
        $user->setRelation('userPrivilege', $userPrivilege);

        Auth::setUser($user);

        $this->assertFalse(PelaporanKegiatanJurusanOverview::canView());
    }

    #[Test]
    public function it_denies_access_to_user_biasa(): void
    {
        $user = new User(['name' => 'User Biasa Test']);
        $privilege = new Privilege(['nama' => 'User Biasa']);
        $userPrivilege = new UserPrivilege();
        $userPrivilege->setRelation('privilege', $privilege);
        $user->setRelation('userPrivilege', $userPrivilege);

        Auth::setUser($user);

        $this->assertFalse(PelaporanKegiatanJurusanOverview::canView());
    }

    #[Test]
    public function it_allows_access_to_admin_and_staff(): void
    {
        $user = new User(['name' => 'Admin Test']);
        $privilege = new Privilege(['nama' => 'Admin']);
        $userPrivilege = new UserPrivilege();
        $userPrivilege->setRelation('privilege', $privilege);
        $user->setRelation('userPrivilege', $userPrivilege);

        Auth::setUser($user);

        $this->assertTrue(PelaporanKegiatanJurusanOverview::canView());
    }

    #[Test]
    public function it_initializes_with_default_year_as_all(): void
    {
        $widget = new PelaporanKegiatanJurusanOverview();
        $widget->mount();

        $this->assertSame('all', $widget->selectedYear);
    }
}
