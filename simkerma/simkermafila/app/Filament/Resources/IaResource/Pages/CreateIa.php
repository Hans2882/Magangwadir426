<?php

namespace App\Filament\Resources\IaResource\Pages;

use App\Filament\Resources\IaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIa extends CreateRecord
{
    protected static string $resource = IaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['jenis_dokumen_id'] = 4;

        if (!empty($data['tanggal_awal']) && empty($data['tanggal_akhir'])) {
            try {
                $raw = trim((string) $data['tanggal_awal']);
                if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $raw, $m)) {
                    $c = \Carbon\Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1]);
                } elseif (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $raw, $m)) {
                    $c = \Carbon\Carbon::createFromDate((int)$m[1], (int)$m[2], (int)$m[3]);
                } else {
                    $c = \Carbon\Carbon::parse($raw);
                }
                $data['tanggal_akhir'] = $c->addYear()->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        return $data;
    }

}
