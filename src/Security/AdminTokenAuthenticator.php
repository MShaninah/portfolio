<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\AdminAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class AdminTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly AdminAuthService $auth) {}

    public function supports(Request $request): ?bool
    {
        if (!str_starts_with($request->getPathInfo(), '/api/admin')) {
            return false;
        }
        if ($request->getPathInfo() === '/api/admin/login') {
            return false;
        }
        return true;
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $auth = (string)$request->headers->get('Authorization', '');
        $token = preg_replace('/^Bearer\s+/i', '', $auth) ?? '';
        if (!$this->auth->verifyToken(trim($token))) {
            throw new AuthenticationException('Invalid token');
        }

        $userProvider = fn(string $identifier) => new InMemoryUser($identifier, null, ['ROLE_ADMIN']);
        return new SelfValidatingPassport(new UserBadge('admin', $userProvider));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
