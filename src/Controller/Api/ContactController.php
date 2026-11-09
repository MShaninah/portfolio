<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\ContactRequest;
use App\Service\ContactService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
final class ContactController extends AbstractController
{
    public function __construct(
        #[Target('contact_form')]
        private readonly RateLimiterFactoryInterface $contactFormLimiter,
        private readonly ContactService $contactService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        Request $request,
        #[MapRequestPayload]
        ContactRequest $dto,
    ): JsonResponse {
        $limit = $this->contactFormLimiter->create($request->getClientIp() ?? 'unknown')->consume();

        if (!$limit->isAccepted()) {
            return $this->json(
                ['message' => 'Too many requests'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $limit->getRetryAfter()->getTimestamp() - time()],
            );
        }

        if (null !== $dto->company && '' !== trim($dto->company)) {
            $this->logger->info('Contact form honeypot triggered.', ['ip' => $request->getClientIp()]);

            return $this->json(['message' => 'Sent']);
        }

        try {
            $this->contactService->sendContactEmail($dto);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Contact email could not be sent.', ['exception' => $e]);

            return $this->json(
                ['message' => 'Service unavailable. Please try again later.'],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return $this->json(['message' => 'Sent']);
    }
}
