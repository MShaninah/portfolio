<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProjectUpdateDto
{
    #[Assert\Length(max: 160)]
    public ?string $title = null;

    #[Assert\Length(max: 200)]
    public ?string $tagline = null;

    public ?string $summary = null;

    /** @var string[]|null */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Length(max: 40),
    ])]
    public ?array $stack = null;

    #[Assert\Length(max: 20)]
    public ?string $year = null;

    #[Assert\Choice(choices: ['Backend','AI','Enterprise','IoT'])]
    public ?string $category = null;
}
