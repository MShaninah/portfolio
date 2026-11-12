<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Profile;
use App\Entity\Skill;
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
        private CvStorage $cv,
    ) {
    }

    /**
     * @return array{profile: mixed, skills: list<array{name: string, category: string, level: string}>, projects: mixed, cv: array{available: bool, filename: string|null, updated_at: string|null}}
     *
     * @throws ExceptionInterface
     */
    public function load(): array
    {
        $profile = $this->profiles->findOneBy([]) ?? new Profile();

        return [
            'profile' => $this->normalizer->normalize($profile, null, ['groups' => 'portfolio:read']),
            'skills' => array_map(
                static fn (Skill $skill): array => [
                    'name' => $skill->getName(),
                    'category' => $skill->getCategory()->value,
                    'level' => $skill->getLevel()->value,
                ],
                $this->skills->findBy([], ['category' => 'ASC', 'name' => 'ASC']),
            ),
            'projects' => $this->normalizer->normalize(
                $this->projects->findBy([], ['id' => 'DESC']),
                null,
                ['groups' => 'project:read'],
            ),
            'cv' => [
                'available' => $this->cv->exists(),
                'filename' => $this->cv->exists() ? $this->cv->downloadName() : null,
                'updated_at' => $this->cv->updatedAt()?->format(\DATE_ATOM),
            ],
        ];
    }
}
