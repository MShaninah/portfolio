<?php

declare(strict_types=1);

namespace App\Security;

use SensitiveParameter;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const string IDENTIFIER = 'admin';

    public function __construct(
        #[SensitiveParameter]
        private string $passwordHash,
    ) {
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getUserIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }
}
