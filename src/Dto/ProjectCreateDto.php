<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\ProjectCategory;
use Symfony\Component\Validator\Constraints as Assert;

final class ProjectCreateDto
{
    #[Assert\NotBlank(message: 'Missing id')]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: '/^[a-z0-9][a-z0-9-]*$/', message: 'Invalid id')]
    public string $id = '';

    #[Assert\NotBlank(message: 'Missing title')]
    #[Assert\Length(max: 128)]
    public string $title = '';

    #[Assert\NotBlank(message: 'Missing year')]
    #[Assert\Length(max: 20)]
    public string $year = '';

    #[Assert\Length(max: 200)]
    public string $tagline = '';

    #[Assert\Length(max: 20000)]
    public string $summary = '';

    /** @var list<string> */
    #[Assert\Count(max: 50)]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
        new Assert\Length(max: 40),
    ])]
    public array $stack = [];

    /** @var list<array{label: string, href: string}>|null */
    #[Assert\Valid]
    #[Assert\Count(max: 20)]
    #[Assert\All([new Assert\Collection(
        fields: [
            'label' => new Assert\Required([
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Length(max: 60),
            ]),
            'href' => new Assert\Required([
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Length(max: 2048),
                new Assert\Regex(
                    pattern: '#^(https?://|/|\#)#',
                    message: 'Link must be an http(s) URL, a root-relative path or an anchor.',
                ),
            ]),
        ],
        allowExtraFields: false,
    )])]
    public ?array $links = null;

    #[Assert\Choice(callback: [ProjectCategory::class, 'values'])]
    public string $category = ProjectCategory::Backend->value;
}
