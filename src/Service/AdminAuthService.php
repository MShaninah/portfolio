<?php

declare(strict_types=1);

namespace App\Service;

final readonly class AdminAuthService
{
    public function __construct(
        private string $adminPassword
    ) {}

    public function verifyPassword(string $password): bool
    {
        return hash_equals($this->adminPassword, $password);
    }

    public function token(): string
    {
        return hash('sha256', $this->adminPassword);
    }

    public function verifyToken(string $token): bool
    {
        return hash_equals($this->token(), trim($token));
    }
}
