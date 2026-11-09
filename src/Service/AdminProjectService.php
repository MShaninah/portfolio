<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ProjectCreateDto;
use App\Dto\ProjectUpdateDto;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class AdminProjectService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProjectRepository $projects,
    ) {
    }

    public function create(ProjectCreateDto $dto): Project
    {
        if (null !== $this->projects->findOneBy(['slug' => $dto->id])) {
            throw new ConflictHttpException('Project with this ID already exists.');
        }

        $project = new Project()
            ->setSlug($dto->id)
            ->setTitle($dto->title)
            ->setTagline($dto->tagline)
            ->setSummary($dto->summary)
            ->setHighlights($dto->stack)
            ->setLinks($dto->links)
            ->setYear($dto->year)
            ->setCategory($dto->category);

        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    public function update(Project $project, ProjectUpdateDto $dto): Project
    {
        if (null !== $dto->title) {
            $project->setTitle($dto->title);
        }

        if (null !== $dto->tagline) {
            $project->setTagline($dto->tagline);
        }

        if (null !== $dto->summary) {
            $project->setSummary($dto->summary);
        }

        if (null !== $dto->stack) {
            $project->setHighlights($dto->stack);
        }

        if (null !== $dto->links) {
            $project->setLinks($dto->links);
        }

        if (null !== $dto->year) {
            $project->setYear($dto->year);
        }

        if (null !== $dto->category) {
            $project->setCategory($dto->category);
        }

        $this->em->flush();

        return $project;
    }

    public function delete(Project $project): void
    {
        $this->em->remove($project);
        $this->em->flush();
    }
}
