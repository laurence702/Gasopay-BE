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
        if(!$request->user() || !$request->user()->hasRole(RoleEnum::Admin)) {
            abort(403, 'Unauthorized action.');
        }

        // Get the branch ID from the route parameter
        $branchParam = $request->route('branch');
        $branchId = $branchParam instanceof \App\Models\Branch ? $branchParam->id : $branchParam;

        // If the user is trying to modify their own branch, deny access
        if ($request->user()->branch_id === $branchId) {
            return response()->json([
                'message' => 'You cannot modify your own branch.'
            ], 403);
        }

        return $next($request);
    }
}
