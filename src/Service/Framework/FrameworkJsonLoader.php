<?php

namespace App\Service\Framework;

use App\Exception\FrameworkException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class FrameworkJsonLoader
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function load(string $promotion, string $year): array
    {
        $promotion = strtolower(trim($promotion));
        $year = trim($year);

        if (!preg_match('/^[a-z0-9]{2,12}$/', $promotion)) {
            throw new FrameworkException('Promotion invalide.');
        }

        if (!preg_match('/^(?<y1>\d{4})-(?<y2>\d{4})$/', $year, $m)) {
            throw new FrameworkException('Année académique invalide.');
        }

        $y1 = (int)$m['y1'];
        $y2 = (int)$m['y2'];

        if ($y2 !== $y1 + 1) {
            throw new FrameworkException('Année académique incohérente.');
        }

        if ($y1 < 2000 || $y1 > 2100) {
            throw new FrameworkException('Année académique hors plage.');
        }

        $filename = sprintf('%s_%s.json', $promotion, $year);
        $path = $this->projectDir.'/public/data/'.$filename;

        if (!is_file($path) || !is_readable($path)) {
            throw new FrameworkException('Référentiel introuvable pour ce contexte.');
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new FrameworkException('Impossible de lire le référentiel.');
        }

        try {
            /** @var array<mixed> $data */
            $data = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new FrameworkException('Le référentiel est invalide.');
        }

        return $data;
    }
}
