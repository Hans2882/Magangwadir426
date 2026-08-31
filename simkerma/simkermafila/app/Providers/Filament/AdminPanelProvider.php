<?php

namespace App\Providers\Filament;

use Filament\Navigation\MenuItem;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->favicon(asset('favicon.png'))
            ->brandLogoHeight('3rem')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->brandName('SIMKERMA')
            ->brandLogo(fn () => new \Illuminate\Support\HtmlString('<div></div>'))
            ->renderHook(
                \Filament\View\PanelsRenderHook::TOPBAR_START,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@include("filament.logo")')
            )
            ->colors([
                'primary' => Color::hex('#113261'),
            ])
            ->navigationGroups([
                'Data Mitra',
                'Data Kerjasama',
                'Pelaporan & Tracking',
                'Inisiasi Kerjasama',
                'Simmagang',
                'Mitra Awards',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->userMenuItems([
                'privilege' => MenuItem::make()
                    ->label(fn () => \Illuminate\Support\Facades\Auth::user()->userPrivilege?->privilege?->nama ?: 'Privilege')
                    ->icon(fn () => \Illuminate\Support\Facades\Auth::user()->userPrivilege?->privilege?->nama === 'WADIR 4' ? 'heroicon-o-star' : 'heroicon-o-shield-check')
                    ->visible(fn () => filled(\Illuminate\Support\Facades\Auth::user()->userPrivilege?->privilege?->nama))
                    ->url(fn (): string => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => \Illuminate\Support\Facades\Auth::user()])),

                'prodi' => MenuItem::make()
                    ->label(fn () => \Illuminate\Support\Facades\Auth::user()->userProgramStudi?->programStudi?->nama_prodi ?: 'Program Studi')
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()->userProgramStudi?->programStudi?->nama_prodi !== null),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
