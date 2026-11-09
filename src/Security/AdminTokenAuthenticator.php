<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class AdminTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly AdminTokenManager $tokens,
    ) {
    }

    public function supports(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/admin')
            && '/api/admin/login' !== $request->getPathInfo();
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $header = $request->headers->get('Authorization') ?? '';
        if (1 !== preg_match('/^Bearer\s+(?<token>\S+)$/i', trim($header), $matches)) {
            throw new CustomUserMessageAuthenticationException('Missing bearer token.');
        }

        if (!$this->tokens->verify($matches['token'])) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired token.');
        }

        return new SelfValidatingPassport(new UserBadge(AdminUser::IDENTIFIER));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
