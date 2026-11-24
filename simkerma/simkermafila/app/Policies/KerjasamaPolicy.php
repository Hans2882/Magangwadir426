<?php

namespace App\Policies;

use App\Models\User;

class KerjasamaPolicy extends BasePolicy
{
    public function create(User $user): bool
    {
        if ($user->isMahasiswa() || $user->isDosen() || $user->isKoorMagang()) {
            return true;
        }

        return parent::create($user);
    }

    public function update(User $user, $model): bool
    {
        if ($user->isKoorMagang()) {
            return in_array($model->jenis_dokumen_id, [4, 8, 9]) && (int) $model->user_id === (int) $user->id;
        }

        if ($user->isDosen() && in_array($model->jenis_dokumen_id, [4, 8, 9]) && (int) $model->user_id === (int) $user->id) {
            return true;
        }

        return parent::update($user, $model);
    }

    public function delete(User $user, $model): bool
    {
        if ($user->isKoorMagang()) {
            return in_array($model->jenis_dokumen_id, [4, 8, 9]) && (int) $model->user_id === (int) $user->id;
        }

        if ($user->isDosen() && in_array($model->jenis_dokumen_id, [4, 8, 9]) && (int) $model->user_id === (int) $user->id) {
            return true;
        }

        return parent::delete($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        if ($user->isKoorMagang() || $user->isDosen() || $user->isMahasiswa()) {
            return false;
        }

        return parent::deleteAny($user);
    }
}
