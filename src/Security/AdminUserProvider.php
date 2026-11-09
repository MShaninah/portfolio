<?php

declare(strict_types=1);

namespace App\Security;

use LogicException;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Provides the single administrator from configuration.
 *
 * The @implements tag is load-bearing, not decoration: Symfony 8.1 declares
 * UserProviderInterface as `@template-covariant TUser of UserInterface`, so
 * PHPStan fails the build without it.
 *
 * @implements UserProviderInterface<AdminUser>
 */
final readonly class AdminUserProvider implements UserProviderInterface
{
    public function __construct(
        #[Autowire('%app.admin_password_hash%')]
        #[SensitiveParameter]
        private string $passwordHash,
    ) {
        if (!str_starts_with($this->passwordHash, '$')) {
            throw new LogicException('ADMIN_PASSWORD_HASH must contain a password hash (generate one with "bin/console security:hash-password").');
        }
    }

    public function loadUserByIdentifier(string $identifier): AdminUser
    {
        if (!hash_equals(AdminUser::IDENTIFIER, $identifier)) {
            throw new UserNotFoundException(\sprintf('There is no user "%s".', $identifier));
        }

        return new AdminUser($this->passwordHash);
    }

    public function refreshUser(UserInterface $user): AdminUser
    {
        if (!$user instanceof AdminUser) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of "%s", got "%s".', AdminUser::class, get_debug_type($user)));
        }

        return new AdminUser($this->passwordHash);
    }

    public function supportsClass(string $class): bool
    {
        return AdminUser::class === $class;
    }
}
