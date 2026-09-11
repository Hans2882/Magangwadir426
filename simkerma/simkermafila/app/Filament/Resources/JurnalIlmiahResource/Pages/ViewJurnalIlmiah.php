<?php

namespace App\Filament\Resources\JurnalIlmiahResource\Pages;

use App\Filament\Resources\JurnalIlmiahResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJurnalIlmiah extends ViewRecord
{
    protected static string $resource = JurnalIlmiahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
