<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Symfony\Component\HttpFoundation\Response;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class VerifyJwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = trim(str_replace('Bearer', '', $authHeader));

        try {
            $publicKeyPath = base_path(config('jwt.public_key_path', 'storage/oauth/public.pem'));
            if (!file_exists($publicKeyPath)) {
                return response()->json(['error' => 'Public key not found'], 500);
            }

            $publicKey = InMemory::file($publicKeyPath);

            $config = Configuration::forAsymmetricSigner(
                new Sha256(),
                InMemory::plainText(''),
                $publicKey
            );

            $parsedToken = $config->parser()->parse($token);

            if (!$config->validator()->validate($parsedToken, ...$config->validationConstraints())) {
                return response()->json(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
            }

            $claims = $parsedToken->claims();
            $userId = $claims->get('sub');

            $request->merge(['jwt_user_id' => $userId]);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Token error: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
