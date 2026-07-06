<?php

namespace App\Filament\Resources\IaResource\Pages;

use App\Filament\Resources\IaResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListIas extends ListRecords
{
    protected static string $resource = IaResource::class;

    public function getTabs(): array
    {
        return [
            'Dalam Negeri' => Tab::make()
                ->icon('heroicon-o-building-office-2')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', 'Dalam Negeri')),
            'Luar Negeri' => Tab::make()
                ->icon('heroicon-o-globe-americas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', 'Luar Negeri')),
            'All' => Tab::make()->icon('heroicon-o-list-bullet'),
        ];
    }
}
