<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class SkillsUpdateDto
{
    /** @var list<string> */
    #[Assert\Count(max: 200, maxMessage: 'At most {{ limit }} skills are allowed.')]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
        new Assert\Length(max: 80),
    ])]
    public array $skills = [];
}
