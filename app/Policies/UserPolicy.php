<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleEnum;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can ban the model.
     */
    public function ban(User $user, User $targetUser): bool
    {
        // Only Super Admins can ban users
        if ($user->role !== RoleEnum::SuperAdmin->value) {
            return false;
        }

        // Super Admins cannot ban other Super Admins
        if ($targetUser->role === RoleEnum::SuperAdmin->value) {
            return false;
        }

        return true;
    }
}
