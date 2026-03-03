<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class SkillsUpdateDto
{
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Length(max: 80),
    ])]
    public array $skills = [];
}
