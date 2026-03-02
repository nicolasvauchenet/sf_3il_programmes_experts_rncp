<?php

namespace App\Service\Context;

use App\Dto\Context\ModuleReference;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

final readonly class FrameworkFolderScanner
{
    public function __construct(
        #[Autowire('%app.data_dir%')]
        private string $dataDir,
    ) {
    }

    /**
     * @return ModuleReference[]
     */
    public function listJsonFiles(string $promotion, string $year, string $folderName): array
    {
        $promotion = strtolower(trim($promotion));
        $year = trim($year);
        $folderName = trim($folderName, "/ \t\n\r\0\x0B");

        if ($promotion === '' || $year === '' || $folderName === '') {
            return [];
        }

        $datasetCode = $promotion.'_'.$year;

        $basePath = rtrim($this->dataDir, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$datasetCode
            .DIRECTORY_SEPARATOR.$folderName;

        if (!is_dir($basePath) || !is_readable($basePath)) {
            return [];
        }

        $finder = new Finder();
        $finder
            ->in($basePath)
            ->depth('== 0')
            ->files()
            ->name('*.json');

        $refs = [];

        foreach ($finder as $file) {
            $path = $file->getRealPath();
            if (!is_string($path) || $path === '' || !is_readable($path)) {
                continue;
            }

            $filename = $file->getFilename();
            $fileCode = strtolower((string)pathinfo($filename, PATHINFO_FILENAME));

            if ($fileCode === '') {
                continue;
            }

            $refs[] = new ModuleReference(
                datasetCode: $datasetCode,
                folderName: $folderName,
                fileCode: $fileCode,
                path: $path,
            );
        }

        usort($refs, static fn(ModuleReference $a, ModuleReference $b): int => $a->fileCode <=> $b->fileCode);

        return $refs;
    }
}
