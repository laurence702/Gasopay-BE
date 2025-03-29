<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\RoleEnum;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        $roleEnum = match (str_replace('-', '_', strtolower($role))) {
            'admin' => RoleEnum::Admin,
            'rider' => RoleEnum::Rider,
            'regular' => RoleEnum::Regular,
            'super_admin' => RoleEnum::SuperAdmin,
            default => null,
        };

        if (!$roleEnum || $request->user()->role !== $roleEnum) {
            return response()->json([
                'message' => 'Unauthorized. You need ' . str_replace('_', ' ', $role) . ' role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
