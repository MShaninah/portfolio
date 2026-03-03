<?php

namespace App\Controller\Api;

use App\Service\PortfolioManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PortfolioController extends AbstractController
{
    #[Route('/api/portfolio', methods: ['GET'])]
    public function __invoke(Request $request, PortfolioManager $portfolio): JsonResponse
    {
        $lang = $request->query->get('lang');
        if (!in_array($lang, ['en', 'de'], true)) {
            $lang = 'en';
        }

        $data = $portfolio->load($lang);
        return $this->json($data);
    }
}
