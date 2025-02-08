<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('x-api-key');
        if(empty($apiKey)) {
            abort(403, 'Forbidden');
        }
        $localKey = config('app.key_secret');
        if($localKey!= $apiKey) {
            abort(403, "Forbidden");
        }

        return $next($request);
    }
}
