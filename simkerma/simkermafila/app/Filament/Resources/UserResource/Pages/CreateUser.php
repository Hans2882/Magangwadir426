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
    }
}
