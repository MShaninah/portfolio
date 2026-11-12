<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\SkillCategory;
use App\Enum\SkillLevel;
use Symfony\Component\Validator\Constraints as Assert;

final class SkillsUpdateDto
{
    /**
     * The complete skill set, replacing whatever is stored.
     *
     * @var list<array{name: string, category?: string, level?: string}>
     */
    #[Assert\Count(max: 200, maxMessage: 'At most {{ limit }} skills are allowed.')]
    #[Assert\All([new Assert\Collection(
        fields: [
            'name' => new Assert\Required([
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Length(max: 80),
            ]),
            'category' => new Assert\Optional([
                new Assert\Type('string'),
                new Assert\Choice(callback: [SkillCategory::class, 'values']),
            ]),
            'level' => new Assert\Optional([
                new Assert\Type('string'),
                new Assert\Choice(callback: [SkillLevel::class, 'values']),
            ]),
        ],
        allowExtraFields: false,
    )])]
    public array $skills = [];
}
