<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

final class EditUserInput
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

    #[Assert\Length(min: 4, max: 4096)]
    public ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\Choice(['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT', 'ROLE_USER'])]
    public string $role = 'ROLE_USER';

    public function getEmail(): string
    {
        return strtolower($this->emailPrefix) . '@3il.fr';
    }

    public function setEmail(string $email): void
    {
        $this->emailPrefix = preg_replace('/@3il\.fr$/i', '', strtolower($email)) ?? '';
    }
}
