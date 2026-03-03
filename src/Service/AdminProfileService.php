<?php

namespace App\Service;

use App\Dto\ProfileUpdateDto;
use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AdminProfileService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileRepository      $profiles,
    ) {}

    public function update(ProfileUpdateDto $dto): Profile
    {
        $entity = $this->profiles->findOneBy([]) ?? new Profile();
        $entity->setEmail($dto->email)->setPhone($dto->phone);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }
}
