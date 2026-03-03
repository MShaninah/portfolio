<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProjectCreateDto
{
    #[Assert\NotBlank(message: 'Missing id')]
    #[Assert\Regex(pattern: '/^[a-z0-9][a-z0-9-]*$/', message: 'Invalid id')]
    public string $id = '';

    #[Assert\NotBlank(message: 'Missing title')]
    #[Assert\Length(max: 160)]
    public string $title = '';

    #[Assert\NotBlank(message: 'Missing year')]
    #[Assert\Length(max: 20)]
    public string $year = '';

    #[Assert\Length(max: 200)]
    public string $tagline = '';

    public string $summary = '';

    /** @var string[] */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Length(max: 40),
    ])]
    public array $stack = [];

    #[Assert\Choice(choices: ['Backend','AI','Enterprise','IoT'])]
    public string $category = 'Backend';
}

