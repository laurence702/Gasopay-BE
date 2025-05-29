<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Branch;
use App\Enums\RoleEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any branches.
     */
    public function viewAny(User $user): bool
    {
        return true; // Any authenticated user can view branches
    }

    /**
     * Determine whether the user can view the branch.
     */
    public function view(User $user, Branch $branch): bool
    {
        return true; // Any authenticated user can view branches
    }

    /**
     * Determine whether the user can create branches.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value);
    }

    /**
     * Determine whether the user can update the branch.
     */
    public function update(User $user, Branch $branch): bool
    {
        if ($user->hasRole(RoleEnum::SuperAdmin->value)) {
            return true;
        }

        if ($user->hasRole(RoleEnum::Admin->value)) {
            return $user->branch_id === $branch->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the branch.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value);
    }
} 