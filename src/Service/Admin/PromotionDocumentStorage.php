<?php

namespace App\Service\Admin;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

final readonly class PromotionDocumentStorage
{
    public function __construct(#[Autowire('%kernel.project_dir%/data/documents')] private string $directory) {}

    public function store(File $file): string
    {
        $extension = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
        if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $extension = strtolower($file->getClientOriginalExtension());
        }
        if (!in_array($extension, ['pdf', 'xlsx'], true)) throw new \InvalidArgumentException('Format de document non autorisé.');
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->directory);
        $filesystem->copy($file->getPathname(), $this->path($name));
        return $name;
    }

    public function path(string $name): string
    {
        if (!preg_match('/^[a-f0-9]{32}\.(pdf|xlsx)$/D', $name)) throw new \InvalidArgumentException('Nom de stockage invalide.');
        return $this->directory . '/' . $name;
    }

    public function remove(string $name): void
    {
        if ($name !== '') (new Filesystem())->remove($this->path($name));
    }
}
