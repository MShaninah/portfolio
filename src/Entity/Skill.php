<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SkillCategory;
use App\Enum\SkillLevel;
use App\Repository\SkillRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80, unique: true)]
    #[Groups(['portfolio:read'])]
    private string $name = '';

    #[ORM\Column(length: 32, enumType: SkillCategory::class, options: ['default' => 'backend'])]
    #[Groups(['portfolio:read'])]
    private SkillCategory $category = SkillCategory::Backend;

    #[ORM\Column(length: 32, enumType: SkillLevel::class, options: ['default' => 'advanced'])]
    #[Groups(['portfolio:read'])]
    private SkillLevel $level = SkillLevel::Advanced;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): SkillCategory
    {
        return $this->category;
    }

    public function setCategory(SkillCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getLevel(): SkillLevel
    {
        return $this->level;
    }

    public function setLevel(SkillLevel $level): self
    {
        $this->level = $level;

        return $this;
    }
}
