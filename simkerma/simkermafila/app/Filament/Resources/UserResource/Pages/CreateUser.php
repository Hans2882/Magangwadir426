<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected static ?string $title = 'Buat User';

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();
        if (!empty($data['privilege_id'])) {
            \App\Models\UserPrivilege::create([
                'user_id' => $this->record->id,
                'privilege_id' => $data['privilege_id'],
            ]);
        }
        
        $prodiIds = [];
        if (!empty($data['program_studi_ids'])) {
            $prodiIds = is_array($data['program_studi_ids']) ? $data['program_studi_ids'] : [$data['program_studi_ids']];
        } elseif (!empty($data['program_studi_id'])) {
            $prodiIds = [$data['program_studi_id']];
        }

        if (!empty($prodiIds)) {
            $filteredIds = array_filter($prodiIds);
            $this->record->programStudis()->sync($filteredIds);

            if (!$this->record->jurusan_id && !empty($filteredIds)) {
                $firstProdi = \App\Models\MasterProgramStudi::find(reset($filteredIds));
                if ($firstProdi?->jurusan_id) {
                    $this->record->update(['jurusan_id' => $firstProdi->jurusan_id]);
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
