<?php

namespace App\Service;

use App\Dto\ProjectCreateDto;
use App\Dto\ProjectUpdateDto;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class AdminProjectService
{
    public function __construct(
        private EntityManagerInterface $em,
        private PropertyAccessorInterface $propertyAccessor,
    ) {}

    public function create(ProjectCreateDto $dto): Project
    {
        $repo = $this->em->getRepository(Project::class);

        if ($repo->findOneBy(['slug' => $dto->id])) {
            throw new ConflictHttpException('Project with this ID already exists.');
        }

        $project = new Project();
        $project->setSlug($dto->id);

        $this->mapDtoToEntity($dto, $project);

        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    public function update(Project $project, ProjectUpdateDto $dto): Project
    {
        $this->mapDtoToEntity($dto, $project);

        $this->em->flush();

        return $project;
    }

    public function delete(Project $project): void
    {
        $this->em->remove($project);
        $this->em->flush();
    }
    private function mapDtoToEntity(object $dto, Project $project): void
    {
        $mapping = [
            'title'    => 'title',
            'tagline'  => 'tagline',
            'summary'  => 'summary',
            'stack'    => 'highlights',
            'year'     => 'year',
            'category' => 'category',
            'links'    => 'links',
        ];

        foreach ($mapping as $dtoField => $entityField) {
            if ($this->propertyAccessor->isReadable($dto, $dtoField)) {
                $value = $this->propertyAccessor->getValue($dto, $dtoField);
                if ($value !== null) {
                    $this->propertyAccessor->setValue($project, $entityField, $value);
                }
            }
        }
    }
}
