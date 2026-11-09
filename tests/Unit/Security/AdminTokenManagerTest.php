<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AdminTokenManager;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class AdminTokenManagerTest extends TestCase
{
    private const string SECRET = 'test-secret-value';

    private static function manager(MockClock $clock, int $ttl = 3600, string $secret = self::SECRET): AdminTokenManager
    {
        return new AdminTokenManager($secret, $ttl, $clock);
    }

    public function testIssuedTokenIsAccepted(): void
    {
        $manager = self::manager(new MockClock('2026-11-09 12:00:00'));

        $issued = $manager->issue();

        self::assertSame(3600, $issued['expires_in']);
        self::assertTrue($manager->verify($issued['token']));
    }

    public function testTokenIsNotDerivedFromTheSecret(): void
    {
        // The old scheme was sha256(password), so the token WAS the credential.
        $manager = self::manager(new MockClock('2026-11-09 12:00:00'));

        $token = $manager->issue()['token'];

        self::assertStringNotContainsString(hash('sha256', self::SECRET), $token);
        self::assertStringNotContainsString(self::SECRET, $token);
    }

    public function testEveryTokenIsUniqueEvenWithinTheSameSecond(): void
    {
        $manager = self::manager(new MockClock('2026-11-09 12:00:00'));

        self::assertNotSame($manager->issue()['token'], $manager->issue()['token']);
    }

    public function testTokenIsRejectedOnceItHasExpired(): void
    {
        $clock = new MockClock('2026-11-09 12:00:00');
        $manager = self::manager($clock, ttl: 600);
        $token = $manager->issue()['token'];

        $clock->sleep(599);
        self::assertTrue($manager->verify($token), 'still valid one second before expiry');

        $clock->sleep(2);
        self::assertFalse($manager->verify($token), 'must be rejected after expiry');
    }

    public function testTokenSignedWithAnotherSecretIsRejected(): void
    {
        $clock = new MockClock('2026-11-09 12:00:00');
        $foreign = self::manager($clock, secret: 'a-different-secret')->issue()['token'];

        self::assertFalse(self::manager($clock)->verify($foreign));
    }

    public function testTamperedPayloadIsRejected(): void
    {
        $clock = new MockClock('2026-11-09 12:00:00');
        $manager = self::manager($clock);
        [$version, $payload, $signature] = explode('.', $manager->issue()['token']);

        // Re-encode the claims with an expiry far in the future, keeping the
        // original signature. This is the forgery the HMAC has to stop.
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        self::assertIsString($decoded);

        $claims = json_decode($decoded, true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($claims);
        $claims['exp'] += 86400;

        $reencoded = json_encode($claims, \JSON_THROW_ON_ERROR);
        $forged = rtrim(strtr(base64_encode($reencoded), '+/', '-_'), '=');

        self::assertFalse($manager->verify($version.'.'.$forged.'.'.$signature));
    }

    public function testTamperedSignatureIsRejected(): void
    {
        $clock = new MockClock('2026-11-09 12:00:00');
        $manager = self::manager($clock);
        [$version, $payload] = explode('.', $manager->issue()['token']);

        self::assertFalse($manager->verify($version.'.'.$payload.'.'.rtrim(strtr(base64_encode('nope'), '+/', '-_'), '=')));
    }

    #[DataProvider('malformedTokens')]
    public function testMalformedTokensAreRejected(string $token): void
    {
        self::assertFalse(self::manager(new MockClock('2026-11-09 12:00:00'))->verify($token));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedTokens(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => ['   '];
        yield 'single segment' => ['garbage'];
        yield 'two segments' => ['v1.abc'];
        yield 'four segments' => ['v1.a.b.c'];
        yield 'unknown version' => ['v2.abc.def'];
        yield 'not base64' => ['v1.!!!.???'];
    }

    public function testAnEmptySecretIsRefused(): void
    {
        $this->expectException(LogicException::class);

        self::manager(new MockClock(), secret: '');
    }

    public function testAnAbsurdlyShortTtlIsRefused(): void
    {
        $this->expectException(LogicException::class);

        self::manager(new MockClock(), ttl: 5);
    }
}
