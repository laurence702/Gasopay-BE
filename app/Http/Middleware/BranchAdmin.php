<?php

namespace App\Http\Middleware;

use Closure;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BranchAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and has admin role
        if(!$request->user() || !$request->user()->hasRole(RoleEnum::Admin->value)) {
            return response()->json([
                'message' => 'You need branch admin role to access this resource.'
            ], 403); // Using 403 Forbidden instead of 401 Unauthorized
        }
        return $next($request);
    }
}
