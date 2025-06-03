<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        parent::boot();

        Route::bind('user', function ($value) {
            return \App\Models\User::withTrashed()->findOrFail($value);
        });

        Route::bind('branch', function ($value) {
            return \App\Models\Branch::withTrashed()->findOrFail($value);
        });

        Route::bind('payment_history', function ($value) {
            return \App\Models\PaymentHistory::withTrashed()->findOrFail($value);
        });

        Route::bind('order', function ($value) {
            return \App\Models\Order::withTrashed()->findOrFail($value);
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
