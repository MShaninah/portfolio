<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\SkillsUpdateDto;
use App\Entity\Skill;
use App\Enum\SkillCategory;
use App\Enum\SkillLevel;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AdminSkillsService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SkillRepository $skills,
    ) {
    }

    /**
     * Replaces the stored skill set, adding, updating and removing only what
     * actually changed.
     *
     * @return list<array{name: string, category: string, level: string}>
     */
    public function replaceAll(SkillsUpdateDto $dto): array
    {
        /** @var array<string, array{name: string, category: string, level: string}> $wanted */
        $wanted = [];
        foreach ($dto->skills as $entry) {
            // Last one wins, so a duplicated name cannot create two rows and
            // violate the unique index.
            $wanted[$entry['name']] = [
                'name' => $entry['name'],
                'category' => $entry['category'] ?? SkillCategory::Backend->value,
                'level' => $entry['level'] ?? SkillLevel::Advanced->value,
            ];
        }

        $existing = [];
        foreach ($this->skills->findAll() as $skill) {
            $existing[$skill->getName()] = $skill;
        }

        foreach ($existing as $name => $entity) {
            if (!isset($wanted[$name])) {
                $this->em->remove($entity);
            }
        }

        foreach ($wanted as $name => $entry) {
            $entity = $existing[$name] ?? new Skill()->setName($name);
            $entity
                ->setCategory(SkillCategory::from($entry['category']))
                ->setLevel(SkillLevel::from($entry['level']));

            if (!isset($existing[$name])) {
                $this->em->persist($entity);
            }
        }

        $this->em->flush();

        return array_values($wanted);
    }
}
