<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

final class EditUserInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $fullName = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\Length(min: 4, max: 4096)]
    public ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\Choice(['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT', 'ROLE_USER'])]
    public string $role = 'ROLE_USER';

    public bool $disabled = false;
}
