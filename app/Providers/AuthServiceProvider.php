<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\PaymentHistory;
use App\Policies\PaymentHistoryPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        PaymentHistory::class => PaymentHistoryPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
} 