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
        
        if (!empty($data['program_studi_id'])) {
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
