<?php

namespace App\Filament\Resources\PelaporanCaseStudyResource\Pages;

use App\Filament\Resources\PelaporanCaseStudyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPelaporanCaseStudy extends ViewRecord
{
    protected static string $resource = PelaporanCaseStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
