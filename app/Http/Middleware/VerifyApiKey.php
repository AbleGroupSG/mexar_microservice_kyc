<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $user = User::query()->where('api_key', $apiKey)->first();

        if (!$user) {
            abort(403, 'Forbidden');
        }

        Auth::setUser($user);

        return $next($request);
    }
}
