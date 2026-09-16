<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserInput
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
    #[Assert\Length(min: 4, max: 4096)]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\Choice(['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT', 'ROLE_USER'])]
    public string $role = 'ROLE_USER';

    public function getEmail(): string
    {
        return strtolower($this->emailPrefix) . '@3il.fr';
    }
}
