<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Filament\Resources\MouResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class ListMous extends ListRecords
{
    protected static string $resource = MouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create New Kerja Sama')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Dalam Negeri' => Tab::make()
                ->icon('heroicon-o-building-office-2')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', 'Dalam Negeri')),

            'Luar Negeri' => Tab::make()
                ->icon('heroicon-o-globe-americas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', 'Luar Negeri')),

            'All' => Tab::make()
                ->icon('heroicon-o-list-bullet'),
        ];
    }
}