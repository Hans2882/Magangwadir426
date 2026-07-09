<?php

namespace App\Filament\Resources\PksSpkResource\Pages;

use App\Filament\Resources\PksSpkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPksSpks extends ListRecords
{
    protected static string $resource = PksSpkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah PKS / SPK')
                ->icon('heroicon-o-plus'),
        ];
    }
}