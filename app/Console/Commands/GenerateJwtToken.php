<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class GenerateJwtToken extends Command
{
    protected $signature = 'jwt:generate {sub=123} {--exp=3600}';
    protected $description = 'Генерация JWT-токена с использованием RS256';

    public function handle()
    {
        $sub = $this->argument('sub');
        $exp = $this->option('exp');

        $privateKeyPath = base_path('storage/oauth/private.pem');
        if (!file_exists($privateKeyPath)) {
            $this->error("Private key not found at: $privateKeyPath");
            return 1;
        }

        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($privateKeyPath),
            InMemory::file($privateKeyPath)
        );

        $now = new \DateTimeImmutable();
        $token = $config->builder()
            ->issuedBy('drafter-app')
            ->identifiedBy(bin2hex(random_bytes(8)))
            ->issuedAt($now)
            ->expiresAt($now->modify("+{$exp} seconds"))
            ->relatedTo((string) $sub)
            ->withClaim('role', 'tester')
            ->getToken($config->signer(), $config->signingKey());

        $this->info("JWT Token:");
        $this->line($token->toString());

        return 0;
    }
}
