<?php

namespace App\Controller\Api;

use App\Dto\LoginRequest;
use App\Dto\ProfileUpdateDto;
use App\Dto\ProjectCreateDto;
use App\Dto\ProjectUpdateDto;
use App\Dto\SkillsUpdateDto;
use App\Service\AdminAuthService;
use App\Service\AdminProfileService;
use App\Service\AdminProjectService;
use App\Service\AdminSkillsService;
use App\Service\PortfolioManager;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[Route('/api/admin')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly PortfolioManager $portfolio,
        private readonly AdminAuthService $auth,
        private readonly AdminProjectService $projects,
        private readonly AdminProfileService $profiles,
        private readonly AdminSkillsService $skills,
    ) {}

    #[Route('/login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginRequest $dto
    ): JsonResponse
    {
        $password = $dto->password ?? '';
        if (!$this->auth->verifyPassword($password)) {
            return $this->json(['message' => 'Invalid credentials'], 401);
        }
        return $this->json(['token' => $this->auth->token()]);
    }

    #[Route('/portfolio', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPortfolio(Request $request): JsonResponse
    {
        $lang = $request->query->get('lang', 'en');
        return $this->json($this->portfolio->load($lang));
    }

    /**
     */
    #[Route('/profile', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateProfile(
        #[MapRequestPayload] ProfileUpdateDto $dto,
    ): JsonResponse
    {
        $entity = $this->profiles->update($dto);

        return $this->json($entity, context: [
            'groups' => ['profile:read']
        ]);
    }

    #[Route('/skills', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function setSkills(#[MapRequestPayload] SkillsUpdateDto $dto): JsonResponse
    {
        $names = $this->skills->replaceAll($dto);

        return $this->json([
            'message' => 'OK',
            'skills' => $names
        ]);
    }
    #[Route('/projects', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addProject(#[MapRequestPayload] ProjectCreateDto $dto, ValidatorInterface $validator): JsonResponse
    {
        $this->projects->create($dto);

        return $this->json(['message' => 'Project added'], 201);
    }

    #[Route('/projects/{slug}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateProject(#[MapEntity(mapping: ['slug' => 'slug'])] Project $project, #[MapRequestPayload] ProjectUpdateDto $dto, ValidatorInterface $validator): JsonResponse
    {
        $this->projects->update($project, $dto);
        return $this->json(['message' => 'OK']);
    }

    #[Route('/projects/{slug}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteProject(#[MapEntity(mapping: ['slug' => 'slug'])] Project $project): JsonResponse
    {
        $this->projects->delete($project);
        return $this->json(['message' => 'Deleted']);
    }
}
