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

        if (!empty($data['program_studi_id'])) {
            \App\Models\UserProgramStudi::updateOrCreate(
                ['user_id' => $this->record->id],
                ['program_studi_id' => $data['program_studi_id']]
            );
        } else {
            \App\Models\UserProgramStudi::query()->where('user_id', '=', $this->record->id, 'and')->delete();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
