<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\ResetsRateLimiters;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Covers the admin authentication boundary. None of these touch the database.
 */
final class AdminAuthTest extends WebTestCase
{
    use ResetsRateLimiters;

    /** Matches ADMIN_PASSWORD_HASH in .env.test. */
    private const string PASSWORD = 'test-password';

    /**
     * Each test gets its own client IP so the login throttle, whose state is
     * keyed by IP and persists in the cache, cannot leak between tests.
     */
    private function clientFromIp(string $ip): KernelBrowser
    {
        $client = static::createClient([], ['REMOTE_ADDR' => $ip]);
        $this->resetRateLimiters($ip);

        return $client;
    }

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    private static function postJson(KernelBrowser $client, string $uri, array $payload, array $headers = []): void
    {
        $client->request(
            'POST',
            $uri,
            server: ['CONTENT_TYPE' => 'application/json'] + $headers,
            content: json_encode($payload, \JSON_THROW_ON_ERROR),
        );
    }

    public function testLoginWithTheCorrectPasswordReturnsAnExpiringToken(): void
    {
        $client = $this->clientFromIp('203.0.113.1');

        self::postJson($client, '/api/admin/login', ['password' => self::PASSWORD]);

        self::assertResponseIsSuccessful();
        $body = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('token', $body);
        self::assertNotSame('', $body['token']);
        self::assertSame(3600, $body['expires_in']);
        self::assertArrayHasKey('expires_at', $body);
    }

    public function testTheIssuedTokenIsNotASha256OfThePassword(): void
    {
        // Regression guard for the original scheme, where the bearer token was
        // literally hash('sha256', $adminPassword) and so disclosed it.
        $client = $this->clientFromIp('203.0.113.2');

        self::postJson($client, '/api/admin/login', ['password' => self::PASSWORD]);

        $token = json_decode((string) $client->getResponse()->getContent(), true)['token'];
        self::assertNotSame(hash('sha256', self::PASSWORD), $token);
        self::assertStringNotContainsString(self::PASSWORD, $token);
    }

    public function testLoginWithAWrongPasswordIsUnauthorized(): void
    {
        $client = $this->clientFromIp('203.0.113.3');

        self::postJson($client, '/api/admin/login', ['password' => 'not the password']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertSame(
            ['message' => 'Invalid credentials'],
            json_decode((string) $client->getResponse()->getContent(), true),
        );
    }

    public function testLoginWithoutAPasswordIsRejectedByValidation(): void
    {
        $client = $this->clientFromIp('203.0.113.4');

        self::postJson($client, '/api/admin/login', []);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRepeatedFailuresAreThrottled(): void
    {
        $client = $this->clientFromIp('203.0.113.5');

        // The limiter allows 5 attempts per 15 minutes.
        for ($i = 0; $i < 5; ++$i) {
            self::postJson($client, '/api/admin/login', ['password' => 'wrong']);
            self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED, "attempt $i should still be judged");
        }

        self::postJson($client, '/api/admin/login', ['password' => 'wrong']);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        self::assertResponseHasHeader('Retry-After');
    }

    public function testThrottlingAlsoBlocksTheCorrectPassword(): void
    {
        // Otherwise an attacker could keep guessing and the throttle would only
        // slow down the wrong answers.
        $client = $this->clientFromIp('203.0.113.6');

        for ($i = 0; $i < 5; ++$i) {
            self::postJson($client, '/api/admin/login', ['password' => 'wrong']);
        }

        self::postJson($client, '/api/admin/login', ['password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function testProtectedRouteWithoutATokenIsUnauthorized(): void
    {
        $client = $this->clientFromIp('203.0.113.7');

        $client->request('GET', '/api/admin/portfolio');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertSame(
            ['message' => 'Unauthorized'],
            json_decode((string) $client->getResponse()->getContent(), true),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function badAuthorizationHeaders(): iterable
    {
        yield 'empty bearer' => ['Bearer '];
        yield 'no scheme' => ['abcdef'];
        yield 'wrong scheme' => ['Basic YWRtaW46YWRtaW4='];
        yield 'garbage token' => ['Bearer not-a-real-token'];
        yield 'old sha256 scheme' => ['Bearer '.hash('sha256', self::PASSWORD)];
        yield 'forged structure' => ['Bearer v1.eyJzdWIiOiJhZG1pbiIsImV4cCI6OTk5OTk5OTk5OX0.c2ln'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badAuthorizationHeaders')]
    public function testProtectedRouteRejectsBadTokens(string $header): void
    {
        $client = $this->clientFromIp('203.0.113.8');

        $client->request('GET', '/api/admin/portfolio', server: ['HTTP_AUTHORIZATION' => $header]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
