<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        if (isset($data['privilege_id'])) {
            \App\Models\UserPrivilege::updateOrCreate(
                ['user_id' => $this->record->id],
                ['privilege_id' => $data['privilege_id']]
            );
        } else {
            \App\Models\UserPrivilege::where('user_id', $this->record->id)->delete();
        }

        if (isset($data['program_studi_id'])) {
            \App\Models\UserProgramStudi::updateOrCreate(
                ['user_id' => $this->record->id],
                ['program_studi_id' => $data['program_studi_id']]
            );
        } else {
            \App\Models\UserProgramStudi::where('user_id', $this->record->id)->delete();
        }
    }
}
