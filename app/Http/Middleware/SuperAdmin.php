<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\RoleEnum;

class SuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        if ($request->user()->role !== RoleEnum::SuperAdmin) {
            return response()->json([
                'message' => 'Unauthorized. You need super admin role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
} 