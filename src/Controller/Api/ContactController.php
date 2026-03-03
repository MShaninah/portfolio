<?php

namespace App\Controller\Api;

use App\Dto\ContactRequest;
use App\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
final class ContactController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'limiter.contact_form')]
        private readonly RateLimiterFactory $contactFormLimiter,
        private readonly ContactService     $contactService,
    ) {}

    public function send(
        Request $request,
        #[MapRequestPayload] ContactRequest $dto,
    ): JsonResponse {
        $limiter = $this->contactFormLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many requests'], 429);
        }

        if (!empty($dto->company)) {
            return $this->json(['message' => 'Sent']);
        }

        try {
            $this->contactService->sendContactEmail($dto);
        } catch (TransportExceptionInterface $e) {
            return $this->json(['message' => 'Service unavailable'], 500);
        }

        return $this->json(['message' => 'Sent']);
    }
}
