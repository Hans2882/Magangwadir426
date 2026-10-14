<?php

namespace App\Policies;

use App\Models\User;

class KerjasamaPolicy extends BasePolicy
{
    public function create(User $user): bool
    {
        if ($user->isMahasiswa()) {
            return true;
        }

        return parent::create($user);
    }
}
