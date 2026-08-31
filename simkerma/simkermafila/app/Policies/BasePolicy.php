<?php

namespace App\Policies;

use App\Models\User;

abstract class BasePolicy
{
    /**
     * Helper to get the privilege object for the user.
     */
    protected function getPrivilege(User $user)
    {
        return $user->userPrivilege?->privilege;
    }

    /**
     * Super Admins (is_admin_panel = true) can do everything.
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $this->getPrivilege($user)?->is_admin_panel ?? false;
    }

    public function viewAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $this->getPrivilege($user)?->can_read ?? false;
    }

    public function view(User $user, $model): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $this->getPrivilege($user)?->can_read ?? false;
    }

    public function create(User $user): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $this->getPrivilege($user)?->can_create ?? false;
    }

    public function update(User $user, $model): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $this->getPrivilege($user)?->can_update ?? false;
    }

    public function delete(User $user, $model): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $this->getPrivilege($user)?->can_delete ?? false;
    }

    public function deleteAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) return true;
        return $this->getPrivilege($user)?->can_delete ?? false;
    }

    public function restore(User $user, $model): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function forceDelete(User $user, $model): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }
}
