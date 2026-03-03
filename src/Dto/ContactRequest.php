<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ContactRequest
{
    #[Assert\NotBlank, Assert\Length(max: 120)]
    public string $name;

    #[Assert\NotBlank, Assert\Email, Assert\Length(max: 180)]
    public string $email;

    #[Assert\NotBlank, Assert\Length(max: 180)]
    public string $subject;

    #[Assert\NotBlank, Assert\Length(min: 10, max: 5000)]
    public string $message;

    public ?string $company = null;
}
