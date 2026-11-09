<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AdminTokenManager;
use App\Security\AdminUser;
use App\Security\AdminUserProvider;
use App\Service\AdminAuthService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

final class AdminAuthServiceTest extends TestCase
{
    private const string PASSWORD = 'correct horse battery staple';

    private static function service(): AdminAuthService
    {
        $factory = new PasswordHasherFactory([
            AdminUser::class => ['algorithm' => 'bcrypt', 'cost' => 4],
        ]);

        $hash = $factory->getPasswordHasher(AdminUser::class)->hash(self::PASSWORD);

        return new AdminAuthService(
            new AdminUserProvider($hash),
            $factory,
            new AdminTokenManager('test-secret', 3600, new MockClock('2026-11-09 12:00:00')),
        );
    }

    public function testTheCorrectPasswordIsAccepted(): void
    {
        self::assertTrue(self::service()->verifyPassword(self::PASSWORD));
    }

    public function testAWrongPasswordIsRejected(): void
    {
        self::assertFalse(self::service()->verifyPassword('wrong password'));
    }

    public function testAnEmptyPasswordIsRejected(): void
    {
        self::assertFalse(self::service()->verifyPassword(''));
    }

    public function testThePasswordIsNotComparedAsPlaintext(): void
    {
        // Guards against a regression to hash_equals($plaintextFromConfig, $input).
        $hash = new PasswordHasherFactory([AdminUser::class => ['algorithm' => 'bcrypt', 'cost' => 4]])
            ->getPasswordHasher(AdminUser::class)
            ->hash(self::PASSWORD);

        self::assertStringStartsWith('$2y$', $hash);
        self::assertStringNotContainsString(self::PASSWORD, $hash);
    }

    public function testIssuedTokenCarriesAnExpiry(): void
    {
        $issued = self::service()->issueToken();

        self::assertArrayHasKey('token', $issued);
        self::assertSame(3600, $issued['expires_in']);
        self::assertNotSame('', $issued['expires_at']);
    }
}
