<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $title = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $year = null;

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): self { $this->url = $url; return $this; }

    public function getYear(): ?string { return $this->year; }
    public function setYear(?string $year): self { $this->year = $year; return $this; }
}
