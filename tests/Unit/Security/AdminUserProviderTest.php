<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AdminUser;
use App\Security\AdminUserProvider;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class AdminUserProviderTest extends TestCase
{
    private const string HASH = '$2y$04$abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTU';

    public function testItLoadsTheAdministrator(): void
    {
        $user = new AdminUserProvider(self::HASH)->loadUserByIdentifier('admin');

        self::assertSame('admin', $user->getUserIdentifier());
        self::assertSame(['ROLE_ADMIN'], $user->getRoles());
        self::assertSame(self::HASH, $user->getPassword());
    }

    public function testUnknownIdentifiersAreRejected(): void
    {
        $this->expectException(UserNotFoundException::class);

        new AdminUserProvider(self::HASH)->loadUserByIdentifier('root');
    }

    #[DataProvider('nonHashValues')]
    public function testADefinedButUselessHashIsReportedLoudly(string $value): void
    {
        // The whole point of the rewrite: a bad credential must never degrade
        // silently into "nobody can log in" or, worse, a usable default.
        $this->expectException(LogicException::class);

        new AdminUserProvider($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonHashValues(): iterable
    {
        yield 'empty' => [''];
        yield 'plaintext password' => ['admin'];
        yield 'bare sha256' => [hash('sha256', 'admin')];
    }

    public function testRefreshReturnsAnEquivalentUser(): void
    {
        $provider = new AdminUserProvider(self::HASH);

        self::assertSame(
            self::HASH,
            $provider->refreshUser(new AdminUser(self::HASH))->getPassword(),
        );
    }

    public function testRefreshRejectsForeignUsers(): void
    {
        $this->expectException(UnsupportedUserException::class);

        new AdminUserProvider(self::HASH)->refreshUser(
            new \Symfony\Component\Security\Core\User\InMemoryUser('someone', null),
        );
    }

    public function testSupportsClass(): void
    {
        $provider = new AdminUserProvider(self::HASH);

        self::assertTrue($provider->supportsClass(AdminUser::class));
        self::assertFalse($provider->supportsClass(stdClass::class));
    }
}
