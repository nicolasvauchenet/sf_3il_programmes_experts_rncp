<?php

namespace App\Service\Import;

use App\Enum\ImportMode;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class JsonDatasetLoader
{
    public function __construct(
        private CodeNormalizer $codeNormalizer,
    )
    {
    }

    /**
     * @return array{
     *     structure: array<string, mixed>,
     *     modules: array<string, array<string, mixed>>,
     *     projects: array<string, array<string, mixed>>,
     *     skills: array<string, array<string, mixed>>,
     *     evaluations: array<string, array<string, mixed>>
     * }
     */
    public function loadFromDirectory(string $directory, ImportMode $mode = ImportMode::FULL): array
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException(sprintf('Le dossier "%s" est introuvable.', $directory));
        }

        $files = $this->findJsonFiles($directory);

        $structure = null;
        $modules = [];
        $projects = [];
        $skills = [];
        $evaluations = [];

        foreach ($files as $file) {
            $data = $this->decodeFile($file);

            if ($this->isStructureFile($data)) {
                $structure = $this->normalizeStructure($data);
                continue;
            }

            $type = (string)($data['meta']['type'] ?? '');
            $code = $this->codeNormalizer->normalize((string)($data['meta']['code'] ?? ''));

            if ($type === '' || $code === '') {
                continue;
            }

            $data['meta']['code'] = $code;

            switch ($type) {
                case 'module':
                    $modules[$code] = $data;
                    break;

                case 'project':
                    $projects[$code] = $data;
                    break;

                case 'skill':
                    if ($mode === ImportMode::FULL) {
                        $skills[$code] = $data;
                    }
                    break;

                case 'evaluation':
                    if ($mode === ImportMode::FULL) {
                        $evaluations[$code] = $data;
                    }
                    break;
            }
        }

        if ($structure === null) {
            throw new \RuntimeException(sprintf('Aucun fichier structure.json valide trouvé dans "%s".', $directory));
        }

        return [
            'structure' => $structure,
            'modules' => $modules,
            'projects' => $projects,
            'skills' => $skills,
            'evaluations' => $evaluations,
        ];
    }

    /**
     * @return list<string>
     */
    private function findJsonFiles(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (mb_strtolower($file->getExtension()) !== 'json') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeFile(string $file): array
    {
        $content = file_get_contents($file);

        if ($content === false) {
            throw new \RuntimeException(sprintf('Impossible de lire le fichier "%s".', $file));
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('JSON invalide dans "%s" : %s', $file, $e->getMessage()), previous: $e);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function isStructureFile(array $data): bool
    {
        return isset($data['meta']['datasetCode'], $data['modules'], $data['projects'], $data['skills'], $data['evaluations']);
    }

    /**
     * @param array<string, mixed> $structure
     * @return array<string, mixed>
     */
    private function normalizeStructure(array $structure): array
    {
        foreach (['modules', 'projects', 'skills', 'evaluations'] as $section) {
            if (!isset($structure[$section]) || !is_array($structure[$section])) {
                continue;
            }

            foreach ($structure[$section] as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if (isset($row['code'])) {
                    $row['code'] = $this->codeNormalizer->normalize((string)$row['code']);
                }

                foreach (['projects', 'modules', 'evaluations'] as $relationKey) {
                    if (isset($row[$relationKey]) && is_array($row[$relationKey])) {
                        $row[$relationKey] = $this->codeNormalizer->normalizeMany($row[$relationKey]);
                    }
                }

                $structure[$section][$index] = $row;
            }
        }

        return $structure;
    }
}
