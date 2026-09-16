<?php

namespace App\Service\Admin;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class PromotionJsonArchiveManager
{
    private const DATASET_PATTERN = '/^[a-z0-9]+_\d{4}-\d{4}$/';

    public function __construct(
        #[Autowire('%app.data_dir%')]
        private string $dataDir,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @return list<array{name: string, path: string, fileCount: int, size: int, updatedAt: \DateTimeImmutable|null}>
     */
    public function listDatasets(): array
    {
        $this->ensureDataDirExists();

        $datasets = [];
        $directory = new \DirectoryIterator($this->dataDir);

        foreach ($directory as $item) {
            if (!$item->isDir() || $item->isDot()) {
                continue;
            }

            $name = $item->getFilename();

            if (!$this->isValidDatasetName($name)) {
                continue;
            }

            $datasets[] = [
                'name' => $name,
                'path' => $item->getPathname(),
                'fileCount' => $this->countJsonFiles($item->getPathname()),
                'size' => $this->computeDirectorySize($item->getPathname()),
                'updatedAt' => $this->resolveUpdatedAt($item->getPathname()),
            ];
        }

        usort(
            $datasets,
            static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']),
        );

        return $datasets;
    }

    public function storePendingUpload(UploadedFile $archive): array
    {
        if ($archive->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload impossible, le fichier transmis est invalide.');
        }

        $extension = strtolower((string) $archive->getClientOriginalExtension());

        if ($extension !== 'zip') {
            throw new \RuntimeException('Le fichier doit être une archive ZIP.');
        }

        $pendingDir = $this->getPendingDir();
        $token = bin2hex(random_bytes(16));
        $pendingPath = $pendingDir . DIRECTORY_SEPARATOR . $token . '.zip';
        $originalName = $archive->getClientOriginalName();

        $archive->move($pendingDir, $token . '.zip');

        try {
            $metadata = $this->inspectArchive($pendingPath, $originalName);
            $this->writePendingMetadata($token, $originalName);
        } catch (\Throwable $e) {
            @unlink($pendingPath);
            @unlink($this->getPendingMetadataPath($token));

            throw $e;
        }

        return [
            'token' => $token,
            'path' => $pendingPath,
            ...$metadata,
        ];
    }

    /**
     * @return array{datasetName: string, jsonFileCount: int}
     */
    public function inspectArchive(string $archivePath, string $originalName): array
    {
        $entries = $this->collectArchiveEntries($archivePath);
        $datasetName = $this->resolveDatasetName($entries, $originalName);

        return [
            'datasetName' => $datasetName,
            'jsonFileCount' => count($entries),
        ];
    }

    public function datasetExists(string $datasetName): bool
    {
        $this->assertValidDatasetName($datasetName);

        return is_dir($this->dataDir . DIRECTORY_SEPARATOR . $datasetName);
    }

    public function importPendingArchive(string $token, bool $replace): string
    {
        $archivePath = $this->getPendingArchivePath($token);
        $originalName = $this->readPendingOriginalName($token);

        try {
            return $this->extractArchive($archivePath, $originalName, $replace);
        } finally {
            @unlink($archivePath);
            @unlink($this->getPendingMetadataPath($token));
        }
    }

    public function getPendingUpload(string $token): array
    {
        $archivePath = $this->getPendingArchivePath($token);
        $originalName = $this->readPendingOriginalName($token);
        $metadata = $this->inspectArchive($archivePath, $originalName);

        return [
            'token' => $token,
            'path' => $archivePath,
            ...$metadata,
        ];
    }
    public function cancelPendingArchive(string $token): void
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return;
        }

        @unlink($this->getPendingDir() . DIRECTORY_SEPARATOR . $token . '.zip');
        @unlink($this->getPendingMetadataPath($token));
    }

    public function deleteDataset(string $datasetName): void
    {
        $this->assertValidDatasetName($datasetName);
        $targetDir = $this->dataDir . DIRECTORY_SEPARATOR . $datasetName;

        if (!is_dir($targetDir)) {
            throw new \RuntimeException('Le dossier demandé est introuvable.');
        }

        $this->removeDirectory($targetDir);
    }

    private function extractArchive(string $archivePath, string $originalName, bool $replace): string
    {
        $entries = $this->collectArchiveEntries($archivePath);
        $datasetName = $this->resolveDatasetName($entries, $originalName);
        $targetDir = $this->dataDir . DIRECTORY_SEPARATOR . $datasetName;

        if (is_dir($targetDir)) {
            if (!$replace) {
                throw new DatasetAlreadyExistsException($datasetName);
            }

            $this->removeDirectory($targetDir);
        }

        $this->ensureDirectory($targetDir);

        $zip = new \ZipArchive();

        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Impossible de lire l’archive ZIP.');
        }

        try {
            $rootPrefix = $this->resolveRootPrefix($entries);

            foreach ($entries as $entry) {
                $relativePath = $rootPrefix !== null ? substr($entry, strlen($rootPrefix)) : $entry;
                $relativePath = ltrim($relativePath, '/');

                if ($relativePath === '') {
                    continue;
                }

                $destination = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                $this->ensureDirectory(dirname($destination));
                $this->assertPathInside($destination, $targetDir);

                $source = $zip->getStream($entry);

                if ($source === false) {
                    throw new \RuntimeException(sprintf('Impossible d’extraire "%s".', $entry));
                }

                $target = fopen($destination, 'wb');

                if ($target === false) {
                    fclose($source);

                    throw new \RuntimeException(sprintf('Impossible d’écrire "%s".', $relativePath));
                }

                stream_copy_to_stream($source, $target);
                fclose($source);
                fclose($target);
            }
        } finally {
            $zip->close();
        }

        return $datasetName;
    }

    /**
     * @return list<string>
     */
    private function collectArchiveEntries(string $archivePath): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Impossible de lire l’archive ZIP.');
        }

        $entries = [];

        try {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = (string) $zip->getNameIndex($i);
                $name = str_replace('\\', '/', $name);

                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }

                if ($this->isSystemFile($name)) {
                    continue;
                }

                $this->assertSafeArchivePath($name);

                if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
                    throw new \RuntimeException(sprintf('L’archive contient un fichier non JSON : "%s".', $name));
                }

                $entries[] = $name;
            }
        } finally {
            $zip->close();
        }

        if ($entries === []) {
            throw new \RuntimeException('L’archive ne contient aucun fichier JSON exploitable.');
        }

        return $entries;
    }

    /**
     * @param list<string> $entries
     */
    private function resolveDatasetName(array $entries, string $originalName): string
    {
        $rootPrefix = $this->resolveRootPrefix($entries);

        if ($rootPrefix !== null) {
            return rtrim($rootPrefix, '/');
        }

        $name = strtolower((string) pathinfo($originalName, PATHINFO_FILENAME));

        $this->assertValidDatasetName($name);

        return $name;
    }

    /**
     * @param list<string> $entries
     */
    private function resolveRootPrefix(array $entries): ?string
    {
        $firstSegments = [];

        foreach ($entries as $entry) {
            $segments = explode('/', $entry);

            if (count($segments) < 2) {
                return null;
            }

            $firstSegments[$segments[0]] = true;
        }

        if (count($firstSegments) !== 1) {
            return null;
        }

        $root = (string) array_key_first($firstSegments);

        return $this->isValidDatasetName($root) ? $root . '/' : null;
    }

    private function assertSafeArchivePath(string $path): void
    {
        if (
            str_starts_with($path, '/')
            || preg_match('/^[a-zA-Z]:\//', $path)
            || str_contains($path, '../')
            || str_contains($path, '/..')
            || $path === '..'
        ) {
            throw new \RuntimeException(sprintf('Chemin interdit dans l’archive : "%s".', $path));
        }
    }

    private function isSystemFile(string $path): bool
    {
        $basename = basename($path);

        return str_starts_with($path, '__MACOSX/') || $basename === '.DS_Store';
    }

    private function isValidDatasetName(string $name): bool
    {
        return preg_match(self::DATASET_PATTERN, $name) === 1;
    }

    private function assertValidDatasetName(string $name): void
    {
        if (!$this->isValidDatasetName($name)) {
            throw new \RuntimeException('Le nom du dossier doit respecter la convention promo_yyyy-yyyy, par exemple cdwfs_2026-2027.');
        }
    }

    private function ensureDataDirExists(): void
    {
        $this->ensureDirectory($this->dataDir);
    }

    private function getPendingDir(): string
    {
        $pendingDir = $this->projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'admin-promotion-json-uploads';
        $this->ensureDirectory($pendingDir);

        return $pendingDir;
    }

    private function getPendingArchivePath(string $token): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new \RuntimeException('Archive temporaire introuvable.');
        }

        $archivePath = $this->getPendingDir() . DIRECTORY_SEPARATOR . $token . '.zip';

        if (!is_file($archivePath)) {
            throw new \RuntimeException('Archive temporaire introuvable ou expirée.');
        }

        return $archivePath;
    }

    private function writePendingMetadata(string $token, string $originalName): void
    {
        file_put_contents(
            $this->getPendingMetadataPath($token),
            json_encode(['originalName' => $originalName], JSON_THROW_ON_ERROR),
        );
    }

    private function readPendingOriginalName(string $token): string
    {
        $metadataPath = $this->getPendingMetadataPath($token);

        if (!is_file($metadataPath)) {
            return $token . '.zip';
        }

        $metadata = json_decode((string) file_get_contents($metadataPath), true, 512, JSON_THROW_ON_ERROR);
        $originalName = $metadata['originalName'] ?? null;

        return is_string($originalName) && $originalName !== '' ? $originalName : $token . '.zip';
    }

    private function getPendingMetadataPath(string $token): string
    {
        return $this->getPendingDir() . DIRECTORY_SEPARATOR . $token . '.json';
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier "%s".', $path));
        }
    }

    private function assertPathInside(string $path, string $baseDir): void
    {
        $base = realpath($baseDir);
        $parent = realpath(dirname($path));

        if ($base === false || $parent === false || ($parent !== $base && !str_starts_with($parent, $base . DIRECTORY_SEPARATOR))) {
            throw new \RuntimeException('Extraction bloquée : chemin de destination invalide.');
        }
    }

    private function removeDirectory(string $path): void
    {
        $base = realpath($this->dataDir);
        $target = realpath($path);

        if ($base === false || $target === false || $target === $base || !str_starts_with($target, $base . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('Suppression bloquée : chemin invalide.');
        }

        @chmod($target, 0775);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @chmod($item->getPathname(), 0775);
                rmdir($item->getPathname());

                continue;
            }

            @chmod($item->getPathname(), 0664);
            unlink($item->getPathname());
        }

        @chmod($target, 0775);
        rmdir($target);
    }

    private function countJsonFiles(string $path): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && strtolower($item->getExtension()) === 'json') {
                ++$count;
            }
        }

        return $count;
    }

    private function computeDirectorySize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $size += $item->getSize();
            }
        }

        return $size;
    }

    private function resolveUpdatedAt(string $path): ?\DateTimeImmutable
    {
        $updatedAt = filemtime($path) ?: null;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $item) {
            $updatedAt = max($updatedAt ?? 0, $item->getMTime());
        }

        return $updatedAt === null ? null : (new \DateTimeImmutable())->setTimestamp($updatedAt);
    }
}
