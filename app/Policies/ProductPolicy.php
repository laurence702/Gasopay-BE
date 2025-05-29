<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;
use App\Enums\RoleEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return true; // Any authenticated user can view products
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        return true; // Any authenticated user can view products
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value);
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value);
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value);
    }
} 