<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest
{
    #[Assert\NotBlank(message: 'Password is required')]
    public ?string $password = null;
}
