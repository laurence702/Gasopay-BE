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
        if(!$request->user() && $request->user()->hasRole(RoleEnum::Admin)) {
            return response()->json([
                'message' => 'You need admin role to access this resource.'
            ], 401);
        }
        return $next($request);
    }
}
