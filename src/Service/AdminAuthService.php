<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\AdminTokenManager;
use App\Security\AdminUser;
use App\Security\AdminUserProvider;
use SensitiveParameter;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class AdminAuthService
{
    public function __construct(
        private AdminUserProvider $users,
        private PasswordHasherFactoryInterface $hasherFactory,
        private AdminTokenManager $tokens,
    ) {
    }

    public function verifyPassword(#[SensitiveParameter] string $password): bool
    {
        if ('' === $password) {
            return false;
        }

        $user = $this->users->loadUserByIdentifier(AdminUser::IDENTIFIER);

        return $this->hasherFactory->getPasswordHasher($user)->verify($user->getPassword(), $password);
    }

    /**
     * @return array{token: string, expires_in: int, expires_at: string}
     */
    public function issueToken(): array
    {
        return $this->tokens->issue();
    }
}
