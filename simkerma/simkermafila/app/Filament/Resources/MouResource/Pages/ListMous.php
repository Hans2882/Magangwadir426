<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Filament\Resources\MouResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMous extends ListRecords
{
    protected static string $resource = MouResource::class;

    public function getTabs(): array
    {
        return [
            'Dalam Negeri' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', 'Dalam Negeri')),
            'Luar Negeri' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', 'Luar Negeri')),
            'All' => Tab::make(),
        ];
    }
}
