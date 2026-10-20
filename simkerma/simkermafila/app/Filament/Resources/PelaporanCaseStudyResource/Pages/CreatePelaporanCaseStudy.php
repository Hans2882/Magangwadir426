<?php

namespace App\Filament\Resources\PelaporanCaseStudyResource\Pages;

use App\Filament\Resources\PelaporanCaseStudyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePelaporanCaseStudy extends CreateRecord
{
    protected static string $resource = PelaporanCaseStudyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $data['user_id'] = $user?->id;
        if ($user?->isMahasiswa()) {
            $data['status_workflow'] = 'Proses';
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
