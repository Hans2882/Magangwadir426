<?php

namespace App\Filament\Resources\PelaporanCaseStudyResource\Pages;

use App\Filament\Resources\PelaporanCaseStudyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPelaporanCaseStudies extends ListRecords
{
    protected static string $resource = PelaporanCaseStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
