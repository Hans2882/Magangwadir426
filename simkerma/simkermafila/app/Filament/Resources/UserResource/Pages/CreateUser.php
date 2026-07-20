<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();
        if (isset($data['privilege_id'])) {
            \App\Models\UserPrivilege::create([
                'user_id' => $this->record->id,
                'privilege_id' => $data['privilege_id'],
            ]);
        }
        
        if (isset($data['program_studi_id'])) {
            \App\Models\UserProgramStudi::create([
                'user_id' => $this->record->id,
                'program_studi_id' => $data['program_studi_id'],
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
