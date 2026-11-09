<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\LoginRequest;
use App\Dto\ProfileUpdateDto;
use App\Dto\ProjectCreateDto;
use App\Dto\ProjectUpdateDto;
use App\Dto\SkillsUpdateDto;
use App\Entity\Project;
use App\Service\AdminAuthService;
use App\Service\AdminProfileService;
use App\Service\AdminProjectService;
use App\Service\AdminSkillsService;
use App\Service\PortfolioManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly PortfolioManager $portfolio,
        private readonly AdminAuthService $auth,
        private readonly AdminProjectService $projects,
        private readonly AdminProfileService $profiles,
        private readonly AdminSkillsService $skills,
    ) {
    }

    #[Route('/login', name: 'api_admin_login', methods: ['POST'])]
    public function login(
        #[MapRequestPayload]
        LoginRequest $dto,
        Request $request,
        #[Target('admin_login')]
        RateLimiterFactoryInterface $adminLoginLimiter,
        LoggerInterface $logger,
    ): JsonResponse {
        $limit = $adminLoginLimiter->create($request->getClientIp() ?? 'unknown')->consume();

        if (!$limit->isAccepted()) {
            $logger->warning('Admin login throttled.', ['ip' => $request->getClientIp()]);

            return $this->json(
                ['message' => 'Too many login attempts. Try again later.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $limit->getRetryAfter()->getTimestamp() - time()],
            );
        }

        if (!$this->auth->verifyPassword($dto->password ?? '')) {
            $logger->warning('Failed admin login attempt.', ['ip' => $request->getClientIp()]);

            return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($this->auth->issueToken());
    }

    #[Route('/portfolio', name: 'api_admin_portfolio', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPortfolio(): JsonResponse
    {
        return $this->json($this->portfolio->load());
    }

    #[Route('/profile', name: 'api_admin_profile_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateProfile(#[MapRequestPayload] ProfileUpdateDto $dto): JsonResponse
    {
        return $this->json(
            ['profile' => $this->profiles->update($dto)],
            context: ['groups' => ['portfolio:read']],
        );
    }

    #[Route('/skills', name: 'api_admin_skills_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function setSkills(#[MapRequestPayload] SkillsUpdateDto $dto): JsonResponse
    {
        return $this->json([
            'message' => 'OK',
            'skills' => $this->skills->replaceAll($dto),
        ]);
    }

    #[Route('/projects', name: 'api_admin_projects_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addProject(#[MapRequestPayload] ProjectCreateDto $dto): JsonResponse
    {
        $this->projects->create($dto);

        return $this->json(['message' => 'Project added'], Response::HTTP_CREATED);
    }

    #[Route('/projects/{slug}', name: 'api_admin_projects_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateProject(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Project $project,
        #[MapRequestPayload]
        ProjectUpdateDto $dto,
    ): JsonResponse {
        $this->projects->update($project, $dto);

        return $this->json(['message' => 'OK']);
    }

    #[Route('/projects/{slug}', name: 'api_admin_projects_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteProject(#[MapEntity(mapping: ['slug' => 'slug'])] Project $project): JsonResponse
    {
        $this->projects->delete($project);

        return $this->json(['message' => 'Deleted']);
    }
}
