<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\PortfolioManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PortfolioController extends AbstractController
{
    #[Route('/api/portfolio', name: 'api_portfolio', methods: ['GET'])]
    public function __invoke(PortfolioManager $portfolio): JsonResponse
    {
        return $this->json($portfolio->load());
    }
}
