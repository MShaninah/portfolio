<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\ProjectCategory;
use Symfony\Component\Validator\Constraints as Assert;

final class ProjectUpdateDto
{
    #[Assert\Length(max: 128)]
    public ?string $title = null;

    #[Assert\Length(max: 200)]
    public ?string $tagline = null;

    #[Assert\Length(max: 20000)]
    public ?string $summary = null;

    /** @var list<string>|null */
    // These are the "Key highlights" bullet points shown on a project card,
    // not short technology tags. The previous 40-character cap was never hit
    // because the admin could not reach this endpoint at all; once it could,
    // it rejected every existing project, whose bullets run 58-119 characters.
    #[Assert\Count(max: 50)]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
        new Assert\Length(max: 200),
    ])]
    public ?array $stack = null;

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

    #[Assert\Length(max: 20)]
    public ?string $year = null;

    #[Assert\Choice(callback: [ProjectCategory::class, 'values'])]
    public ?string $category = null;
}
