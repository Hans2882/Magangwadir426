<?php

namespace App\Filament\Resources\Traits;

use App\Models\Kerjasama;

trait HasKerjasamaReference
{
    protected function setMitraFromKerjasamaReference(array $data): array
    {
        $referenceIds = $data['dokumenTerkait'] ?? [];
        if (is_scalar($referenceIds) && $referenceIds !== '') {
            $referenceIds = [$referenceIds];
        }

        if (! empty($referenceIds)) {
            $reference = Kerjasama::query()
                ->whereIn('jenis_dokumen_id', [1, 3, 4])
                ->findOrFail($referenceIds[0]);

            $data['mitra_id'] = $reference->mitra_id;
        }

        return $data;
    }
}