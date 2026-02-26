<?php

namespace App\Service\Context;

use App\Dto\Context\FrameworkStructure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class FrameworkStructureLoader
{
    public function __construct(
        #[Autowire('%app.data_dir%')]
        private string $dataDir,
    )
    {
    }

    public function load(string $promotion, string $year): FrameworkStructure
    {
        $promotion = strtolower(trim($promotion));
        $year = trim($year);

        $folder = $promotion . '_' . $year;
        $path = rtrim($this->dataDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $folder
            . DIRECTORY_SEPARATOR . 'structure.json';

        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(sprintf('structure.json introuvable pour "%s".', $folder));
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            throw new \RuntimeException(sprintf('structure.json illisible pour "%s".', $folder));
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('JSON invalide pour "%s".', $folder), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('JSON inattendu (pas un objet) pour "%s".', $folder));
        }

        $meta = $decoded['meta'] ?? null;
        if (!is_array($meta)) {
            throw new \RuntimeException(sprintf('Clé "meta" manquante pour "%s".', $folder));
        }

        $blocks = is_array($decoded['blocks'] ?? null) ? $decoded['blocks'] : [];
        $modules = is_array($decoded['modules'] ?? null) ? $decoded['modules'] : [];
        $skills = is_array($decoded['skills'] ?? null) ? $decoded['skills'] : [];
        $evaluations = is_array($decoded['evaluations'] ?? null) ? $decoded['evaluations'] : [];

        return new FrameworkStructure(
            meta: $meta,
            modules: $modules,
            skills: $skills,
            blocks: $blocks,
            evaluations: $evaluations,
            raw: $decoded,
        );
    }
}
