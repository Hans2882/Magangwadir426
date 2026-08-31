<?php

namespace App\Filament\Resources\PelaporanCaseStudyResource\Pages;

use App\Filament\Resources\PelaporanCaseStudyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPelaporanCaseStudy extends EditRecord
{
    protected static string $resource = PelaporanCaseStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
