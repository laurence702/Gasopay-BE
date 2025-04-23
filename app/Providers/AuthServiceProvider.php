<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('verify-rider', function (User $user) {
            return $user->role === RoleEnum::SuperAdmin || $user->role === RoleEnum::Admin;
        });

        Gate::define('update-verification-status', function (User $user) {
            return $user->role === RoleEnum::Admin;
        });
    }
} 