<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $fullName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 170)]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9._%+\-]+$/',
        message: 'Utilisez uniquement la partie avant @3il.fr'
    )]
    public string $emailPrefix = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096)]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\Choice(['ROLE_TEACHER', 'ROLE_STUDENT'])]
    public string $role = 'ROLE_STUDENT';

    public function getEmail(): string
    {
        return strtolower($this->emailPrefix).'@3il.fr';
    }
}
