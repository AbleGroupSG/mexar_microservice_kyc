<?php

namespace App\Http\Middleware;

use App\Models\UserApiKey;
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
        if (empty($apiKey)) {
            abort(401, 'Unauthorized');
        }

        // Look up the API key in users_api_keys table
        $userApiKey = UserApiKey::query()
            ->with('user')
            ->where('api_key', $apiKey)
            ->first();

        if (!$userApiKey || !$userApiKey->user) {
            abort(401, 'Unauthorized');
        }

        // Set the user for Auth facade
        Auth::setUser($userApiKey->user);

        // Store the UserApiKey instance in the request for controllers to access
        $request->attributes->set('user_api_key', $userApiKey);

        return $next($request);
    }
}
