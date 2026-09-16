<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProfileInput
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

    #[Assert\Length(min: 8, max: 4096)]
    public ?string $password = null;

    public function getEmail(): string
    {
        return strtolower($this->emailPrefix) . '@3il.fr';
    }

    public function setEmail(string $email): void
    {
        $this->emailPrefix = preg_replace('/@3il\.fr$/i', '', strtolower($email)) ?? '';
    }
}
