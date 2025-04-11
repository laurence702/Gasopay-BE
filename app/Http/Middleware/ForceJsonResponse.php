<?php
namespace App\Http\Middleware;  

use Closure;  
use Illuminate\Http\JsonResponse;  

class ForceJsonResponse  
{  
    /**  
     * Handle an incoming request.  
     *  
     * @param  \Illuminate\Http\Request  $request  
     * @param  \Closure  $next  
     * @return mixed  
     */  
    public function handle($request, Closure $next)  
    {  
        $response = $next($request);  

        if (!$request->wantsJson()) {  
            return response()->json([  
                'message' => 'Only JSON responses are allowed.',  
                'status' => 'error',  
            ], 406); // HTTP 406 Not Acceptable  
        }  

        return $response;  
    }  
}  