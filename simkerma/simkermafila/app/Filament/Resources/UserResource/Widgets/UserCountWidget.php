<?php

namespace App\Filament\Resources\UserResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserCountWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', \App\Models\User::count())
                ->description('All registered users in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
        ];
    }
}
