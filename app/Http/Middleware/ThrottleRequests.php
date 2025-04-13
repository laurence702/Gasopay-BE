<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests as BaseThrottleRequests;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;

class ThrottleRequests extends BaseThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        if ($user = $request->user()) {
            return sha1($user->getAuthIdentifier());
        }

        if ($route = $request->route()) {
            return sha1($route->getDomain().'|'.$request->ip());
        }

        throw new \RuntimeException('Unable to generate the request signature. Route unavailable.');
    }

    protected function getRateLimit(Request $request)
    {
        $user = $request->user();
        
        // Default limits
        $limits = [
            'default' => 60, // 60 requests per minute
            'auth' => 5,     // 5 requests per minute for auth endpoints
        ];

        // Role-based limits
        if ($user) {
            switch ($user->role) {
                case 'admin':
                    $limits['default'] = 120;
                    break;
                case 'super_admin':
                    $limits['default'] = 240;
                    break;
                case 'rider':
                    $limits['default'] = 90;
                    break;
            }
        }

        // Endpoint-specific limits
        $path = $request->path();
        if (Str::startsWith($path, 'api/auth')) {
            return $limits['auth'];
        }

        return $limits['default'];
    }

    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        $maxAttempts = $this->getRateLimit($request);
        
        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
} 