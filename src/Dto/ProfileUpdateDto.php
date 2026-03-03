<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProfileUpdateDto
{
    #[Assert\NotBlank(message: 'Email required')]
    #[Assert\Email(message: 'Invalid email')]
    public string $email = '';

    public ?string $phone = null;
}
