<?php

namespace App\Filament\Resources\MitraResource\Pages;

use App\Filament\Resources\MitraResource;
use App\Models\Mitra;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMitra extends EditRecord
{
    protected static string $resource = MitraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Mitra $record) {
                    $details = $record->getRelatedDataSummary();
                    if (!empty($details)) {
                        Notification::make()
                            ->title('Tidak Dapat Menghapus Mitra')
                            ->body("Mitra '{$record->nama_mitra}' tidak dapat dihapus karena masih terikat dengan " . implode(', ', $details) . ". Silakan hapus atau alihkan data terkait terlebih dahulu.")
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
