<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    
    protected static ?string $title = 'Edit User';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        if (!empty($data['privilege_id'])) {
            \App\Models\UserPrivilege::updateOrCreate(
                ['user_id' => $this->record->id],
                ['privilege_id' => $data['privilege_id']]
            );
        } else {
            \App\Models\UserPrivilege::query()->where('user_id', '=', $this->record->id, 'and')->delete();
        }

        if (isset($data['program_studi_ids'])) {
            $ids = is_array($data['program_studi_ids']) ? array_filter($data['program_studi_ids']) : [];
            $this->record->programStudis()->sync($ids);
            if (!$this->record->jurusan_id && !empty($ids)) {
                $firstProdi = \App\Models\MasterProgramStudi::find(reset($ids));
                if ($firstProdi?->jurusan_id) {
                    $this->record->update(['jurusan_id' => $firstProdi->jurusan_id]);
                }
            }
        } elseif (isset($data['program_studi_id'])) {
            $ids = !empty($data['program_studi_id']) ? [$data['program_studi_id']] : [];
            $this->record->programStudis()->sync($ids);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
