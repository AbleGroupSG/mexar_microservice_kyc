<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class VerifyJwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized: Missing or invalid token format'], 401);
        }

        try {
            // Remove 'Bearer ' from token
            $token = substr($token, 7);

            // Load the public key from storage
            $publicKeyPath = storage_path('oauth/public.pem');
            $publicKey = InMemory::file($publicKeyPath);

            // Configure JWT validation
            $config = Configuration::forAsymmetricSigner(
                new Sha256(),
                InMemory::empty(), // We don't need private key for verification
                $publicKey
            );

            // Parse and validate the token
            $parsedToken = $config->parser()->parse($token);

            // Verify signature
            $constraint = new SignedWith($config->signer(), $config->verificationKey());
            $config->validator()->assert($parsedToken, $constraint);

            // Add token claims to request if needed
            $request->attributes->set('jwt_claims', $parsedToken->claims()->all());


            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized: Invalid token'], 401);
        }
    }
}
