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

        if (!$request->user()->hasRole($role)) {
            return response()->json([
                'message' => 'Unauthorized. You need ' . $role . ' role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
