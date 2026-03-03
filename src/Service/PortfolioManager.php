<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\ProfileRepository;
use App\Repository\ProjectRepository;
use App\Repository\SkillRepository;
use App\Entity\Profile;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class PortfolioManager
{
    public function __construct(
        private ProfileRepository     $profiles,
        private SkillRepository       $skills,
        private ProjectRepository     $projects,
        private NormalizerInterface   $normalizer, // Use Normalizer to convert objects to arrays
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function load(string $lang = 'en'): array
    {
        $profile = $this->profiles->findOneBy([]) ?? new Profile();
        $skills = $this->skills->findBy([], ['name' => 'ASC']);
        $projects = $this->projects->findBy([], ['id' => 'DESC']);
        $context = ['groups' => 'portfolio:read'];
        $projectContext = ['groups' => 'project:read'];

        return [
            'profile'      => $this->normalizer->normalize($profile, null, $context),
            'skills'       => array_map(static fn($s) => $s->getName(), $skills),
            'projects'     => $this->normalizer->normalize($projects, null, $projectContext),
        ];
    }
}
