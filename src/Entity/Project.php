<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['project:read'])]
    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[Groups(['project:read'])]
    #[ORM\Column(length: 128, nullable: true)]
    private ?string $title = null;

    #[Groups(['project:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tagline = null;

    #[Groups(['project:read'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[Groups(['project:read'])]
    #[ORM\Column(nullable: true)]
    private ?array $highlights = null;

    #[Groups(['project:read'])]
    #[ORM\Column(nullable: true)]
    private ?array $links = null;

    #[Groups(['project:read'])]
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $year = null;

    #[Groups(['project:read'])]
    #[ORM\Column(length: 128, nullable: true)]
    private ?string $category = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(?string $tagline): static
    {
        $this->tagline = $tagline;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getHighlights(): ?array
    {
        return $this->highlights;
    }

    public function setHighlights(?array $highlights): static
    {
        $this->highlights = $highlights;

        return $this;
    }

    public function getLinks(): ?array
    {
        return $this->links;
    }

    public function setLinks(?array $links): static
    {
        $this->links = $links;

        return $this;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(?string $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }
}
