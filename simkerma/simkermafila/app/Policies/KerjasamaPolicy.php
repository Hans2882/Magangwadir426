<?php

namespace App\Policies;

use App\Models\User;

class KerjasamaPolicy extends BasePolicy
{
    public function create(User $user): bool
    {
        if ($user->isMahasiswa() || $user->isDosen()) {
            return true;
        }

        return parent::create($user);
    }

    public function update(User $user, $model): bool
    {
        if ($user->isDosen() && in_array($model->jenis_dokumen_id, [8, 9]) && (int) $model->user_id === (int) $user->id) {
            return true;
        }

        return parent::update($user, $model);
    }

    public function delete(User $user, $model): bool
    {
        if ($user->isDosen() && in_array($model->jenis_dokumen_id, [8, 9]) && (int) $model->user_id === (int) $user->id) {
            return true;
        }

        return parent::delete($user, $model);
    }
}
