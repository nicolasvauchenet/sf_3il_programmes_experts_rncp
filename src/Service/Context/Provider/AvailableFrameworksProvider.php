<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\ContextReference;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

final readonly class AvailableFrameworksProvider
{
    public function __construct(
        #[Autowire('%app.data_dir%')]
        private string $dataDir,
    )
    {
    }

    /**
     * @return ContextReference[]
     */
    public function listAvailable(): array
    {
        if (!is_dir($this->dataDir) || !is_readable($this->dataDir)) {
            return [];
        }

        $finder = new Finder();
        $finder->in($this->dataDir)->depth('== 0')->directories();

        $contexts = [];

        foreach ($finder as $dir) {
            $folderName = $dir->getFilename();

            if (!preg_match('/^(?<promo>[a-z0-9]+)_(?<year>\d{4}-\d{4})$/i', $folderName, $m)) {
                continue;
            }

            $promotionCode = strtolower($m['promo']);
            $academicYear = $m['year'];

            $structurePath = $dir->getRealPath() . DIRECTORY_SEPARATOR . 'structure.json';
            if (!is_file($structurePath) || !is_readable($structurePath)) {
                continue;
            }

            $decoded = $this->safeDecodeJsonFile($structurePath);
            if ($decoded === null) {
                continue;
            }

            $datasetCode = $decoded['meta']['datasetCode'] ?? null;
            if (!is_string($datasetCode) || $datasetCode === '') {
                continue;
            }

            if (strtolower($datasetCode) !== strtolower($folderName)) {
                continue;
            }

            $contexts[] = new ContextReference(
                promotionCode: $promotionCode,
                academicYear: $academicYear,
                datasetCode: $datasetCode,
                structurePath: $structurePath,
            );
        }

        usort($contexts, static fn(ContextReference $a, ContextReference $b): int => [$a->promotionCode, $a->academicYear] <=> [$b->promotionCode, $b->academicYear]
        );

        return $contexts;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeDecodeJsonFile(string $path): ?array
    {
        try {
            $raw = @file_get_contents($path);
            if (!is_string($raw) || $raw === '') {
                return null;
            }

            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
