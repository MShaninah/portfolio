<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects')]
final class ProjectsController extends AbstractController
{
    #[Route('', name: 'api_projects_index', methods: ['GET'])]
    public function index(ProjectRepository $repo): JsonResponse
    {
        return $this->json(
            $repo->findBy([], ['id' => 'DESC']),
            context: ['groups' => 'project:read']
        );
    }

    #[Route('/{slug}', name: 'api_projects_show', methods: ['GET'])]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Project $project): JsonResponse
    {
        return $this->json($project, context: ['groups' => 'project:read']);
    }
}
