<?php

namespace App\Policies;

use App\Models\PaymentHistory;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentHistoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin) || $user->hasRole(RoleEnum::Admin);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaymentHistory $paymentHistory): bool
    {
        if ($user->hasRole(RoleEnum::SuperAdmin) || $user->hasRole(RoleEnum::Admin)) {
            return true;
        }
        return $user->id === $paymentHistory->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin) || $user->hasRole(RoleEnum::Admin);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaymentHistory $paymentHistory): bool
    {
        // Only allow updating non-critical fields or by specific roles, e.g., SuperAdmin
        // Add more granular checks if needed, e.g. payment status is pending
        return $user->hasRole(RoleEnum::SuperAdmin) || $user->hasRole(RoleEnum::Admin);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaymentHistory $paymentHistory): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PaymentHistory $paymentHistory): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PaymentHistory $paymentHistory): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin);
    }

    /**
     * Determine whether the user can mark a cash payment.
     */
    public function markCashPayment(User $user, PaymentHistory $paymentHistory): bool
    {
        // SuperAdmin or Admin can mark.
        // Further checks if admin is tied to the order's branch might be needed in the controller or request.
        return $user->hasRole(RoleEnum::SuperAdmin) || 
               $user->hasRole(RoleEnum::Admin);
    }
}
