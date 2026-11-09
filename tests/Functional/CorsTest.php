<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * CORS must come from the application's allowlist and nothing else.
 *
 * In the test environment CORS_ALLOW_ORIGIN is '["^https?://localhost(:[0-9]+)?$"]'.
 *
 * These use an OPTIONS preflight on purpose: NelmioCorsBundle answers it before
 * routing, so the assertions are about CORS alone and never reach the database.
 */
final class CorsTest extends WebTestCase
{
    private function preflight(string $origin): KernelBrowser
    {
        $client = static::createClient();
        $client->request('OPTIONS', '/api/contact', server: [
            'HTTP_ORIGIN' => $origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ]);

        return $client;
    }

    public function testAnAllowedOriginReceivesTheCorsHeader(): void
    {
        // Regression guard for two separate bugs:
        //  1. the config read CORS_ALLOW_ORIGIN while .env defined
        //     CORS_ALLOW_ORIGINS, so a deploy without .env.local could not boot;
        //  2. the value is a JSON array, and ['%env(CORS_ALLOW_ORIGIN)%']
        //     turned it into one literal string that matched no origin at all.
        $client = $this->preflight('http://localhost:5173');

        self::assertResponseHasHeader('Access-Control-Allow-Origin');
        self::assertSame(
            'http://localhost:5173',
            $client->getResponse()->headers->get('Access-Control-Allow-Origin'),
        );
    }

    public function testADisallowedOriginReceivesNoCorsHeader(): void
    {
        $client = $this->preflight('https://evil.example');

        self::assertNotSame(
            'https://evil.example',
            $client->getResponse()->headers->get('Access-Control-Allow-Origin'),
            'the caller Origin must never simply be reflected back',
        );
    }

    public function testExactlyOneAllowOriginHeaderIsSent(): void
    {
        // Two sources emitting this header (nginx reflecting $http_origin AND
        // NelmioCors) makes browsers reject the response outright.
        $client = $this->preflight('http://localhost:5173');

        self::assertCount(1, $client->getResponse()->headers->all('Access-Control-Allow-Origin'));
    }

    public function testCredentialsAreNotAllowed(): void
    {
        // The API authenticates with a bearer token, never a cookie, so
        // allow_credentials must stay off -- it is what makes a reflected
        // origin dangerous.
        $client = $this->preflight('http://localhost:5173');

        self::assertResponseNotHasHeader('Access-Control-Allow-Credentials');
    }
}
