<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-API-Version', 'v1');
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
