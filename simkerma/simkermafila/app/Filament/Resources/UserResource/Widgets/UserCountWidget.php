<?php

namespace App\Filament\Resources\UserResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserCountWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengguna', \App\Models\User::count())
                ->description('Semua pengguna terdaftar di sistem')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
        ];
    }
}
