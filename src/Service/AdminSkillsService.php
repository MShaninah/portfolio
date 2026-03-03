<?php

namespace App\Service;

use App\Dto\SkillsUpdateDto;
use App\Entity\Skill;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AdminSkillsService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SkillRepository        $skills,
    ) {}
    public function replaceAll(SkillsUpdateDto $dto): array
    {
        $newNames = array_values(array_unique($dto->skills));
        $existingSkills = $this->skills->findAll();

        $existingMap = [];
        foreach ($existingSkills as $skill) {
            $existingMap[$skill->getName()] = $skill;
        }

        foreach ($existingMap as $name => $entity) {
            if (!in_array($name, $newNames, true)) {
                $this->em->remove($entity);
            }
        }

        foreach ($newNames as $name) {
            if (!isset($existingMap[$name])) {
                $s = new Skill();
                $s->setName($name);
                $this->em->persist($s);
            }
        }

        $this->em->flush();

        return $newNames;
    }
}
