<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use App\Repository\ProjectRepository;
use App\Repository\SkillRepository;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class PortfolioManager
{
    public function __construct(
        private ProfileRepository $profiles,
        private SkillRepository $skills,
        private ProjectRepository $projects,
        private NormalizerInterface $normalizer,
    ) {
    }

    /**
     * @return array{profile: mixed, skills: list<string>, projects: mixed}
     *
     * @throws ExceptionInterface
     */
    public function load(): array
    {
        $profile = $this->profiles->findOneBy([]) ?? new Profile();

        return [
            'profile' => $this->normalizer->normalize($profile, null, ['groups' => 'portfolio:read']),
            'skills' => array_map(
                static fn ($skill): string => $skill->getName(),
                $this->skills->findBy([], ['name' => 'ASC']),
            ),
            'projects' => $this->normalizer->normalize(
                $this->projects->findBy([], ['id' => 'DESC']),
                null,
                ['groups' => 'project:read'],
            ),
        ];
    }
}
