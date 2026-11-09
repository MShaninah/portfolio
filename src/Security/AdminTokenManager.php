<?php

declare(strict_types=1);

namespace App\Security;

use JsonException;
use LogicException;
use Psr\Clock\ClockInterface;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdminTokenManager
{
    private const string VERSION = 'v1';

    public function __construct(
        #[Autowire('%kernel.secret%')]
        #[SensitiveParameter]
        private string $secret,
        #[Autowire('%app.admin_token_ttl%')]
        private int $ttl,
        private ClockInterface $clock,
    ) {
        if ('' === $this->secret) {
            throw new LogicException('APP_SECRET must be set to issue admin tokens.');
        }

        if ($this->ttl < 60) {
            throw new LogicException('app.admin_token_ttl must be at least 60 seconds.');
        }
    }

    /**
     * @return array{token: string, expires_in: int, expires_at: string}
     */
    public function issue(): array
    {
        $issuedAt = $this->clock->now();
        $expiresAt = $issuedAt->modify(\sprintf('+%d seconds', $this->ttl));

        $payload = [
            'sub' => AdminUser::IDENTIFIER,
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            // Makes every token unique even within the same second.
            'jti' => bin2hex(random_bytes(16)),
        ];

        $body = self::VERSION.'.'.self::encode(json_encode($payload, \JSON_THROW_ON_ERROR));

        return [
            'token' => $body.'.'.self::encode($this->sign($body)),
            'expires_in' => $this->ttl,
            'expires_at' => $expiresAt->format(\DATE_ATOM),
        ];
    }

    public function verify(#[SensitiveParameter] string $token): bool
    {
        $parts = explode('.', trim($token));
        if (3 !== \count($parts)) {
            return false;
        }

        [$version, $payload, $signature] = $parts;
        if (self::VERSION !== $version) {
            return false;
        }

        $expected = $this->sign($version.'.'.$payload);
        $provided = self::decode($signature);
        if (null === $provided || !hash_equals($expected, $provided)) {
            return false;
        }

        $json = self::decode($payload);
        if (null === $json) {
            return false;
        }

        try {
            $claims = json_decode($json, true, 8, \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        if (!\is_array($claims)
            || !\is_string($claims['sub'] ?? null)
            || !\is_int($claims['exp'] ?? null)
            || !hash_equals(AdminUser::IDENTIFIER, $claims['sub'])
        ) {
            return false;
        }

        return $claims['exp'] > $this->clock->now()->getTimestamp();
    }

    private function sign(string $body): string
    {
        return hash_hmac('sha256', $body, $this->secret, true);
    }

    private static function encode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function decode(string $encoded): ?string
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        return false === $decoded ? null : $decoded;
    }
}
