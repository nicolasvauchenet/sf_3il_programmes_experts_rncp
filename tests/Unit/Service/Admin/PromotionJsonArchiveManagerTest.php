<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Admin;

use App\Service\Admin\DatasetAlreadyExistsException;
use App\Service\Admin\PromotionJsonArchiveManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PromotionJsonArchiveManagerTest extends TestCase
{
    private string $workspace;
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir() . '/promotion_json_manager_' . bin2hex(random_bytes(6));
        $this->dataDir = $this->workspace . '/public/data';
        mkdir($this->dataDir, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);

        parent::tearDown();
    }

    public function testItImportsArchiveWithDatasetRootDirectory(): void
    {
        $archive = $this->createUploadedZip('cdwfs_2026-2027.zip', [
            'cdwfs_2026-2027/structure.json' => '{"meta":{}}',
            'cdwfs_2026-2027/modules/module.json' => '{"code":"M1"}',
        ]);
        $manager = $this->createManager();

        $pendingUpload = $manager->storePendingUpload($archive);
        $datasetName = $manager->importPendingArchive($pendingUpload['token'], false);

        self::assertSame('cdwfs_2026-2027', $datasetName);
        self::assertFileExists($this->dataDir . '/cdwfs_2026-2027/structure.json');
        self::assertFileExists($this->dataDir . '/cdwfs_2026-2027/modules/module.json');
    }

    public function testItImportsArchiveWithoutRootDirectoryFromZipName(): void
    {
        $archive = $this->createUploadedZip('eadl_2026-2027.zip', [
            'structure.json' => '{"meta":{}}',
            'skills/skill.json' => '{"code":"C1"}',
        ]);
        $manager = $this->createManager();

        $pendingUpload = $manager->storePendingUpload($archive);
        $datasetName = $manager->importPendingArchive($pendingUpload['token'], false);

        self::assertSame('eadl_2026-2027', $datasetName);
        self::assertFileExists($this->dataDir . '/eadl_2026-2027/structure.json');
        self::assertFileExists($this->dataDir . '/eadl_2026-2027/skills/skill.json');
    }

    public function testItRequiresReplaceWhenDatasetAlreadyExists(): void
    {
        mkdir($this->dataDir . '/cdwfs_2026-2027', 0775, true);
        file_put_contents($this->dataDir . '/cdwfs_2026-2027/old.json', '{}');

        $archive = $this->createUploadedZip('cdwfs_2026-2027.zip', [
            'cdwfs_2026-2027/structure.json' => '{"meta":{}}',
        ]);
        $manager = $this->createManager();
        $pendingUpload = $manager->storePendingUpload($archive);

        $this->expectException(DatasetAlreadyExistsException::class);

        $manager->importPendingArchive($pendingUpload['token'], false);
    }

    public function testItReplacesExistingDataset(): void
    {
        mkdir($this->dataDir . '/cdwfs_2026-2027', 0775, true);
        file_put_contents($this->dataDir . '/cdwfs_2026-2027/old.json', '{}');

        $archive = $this->createUploadedZip('cdwfs_2026-2027.zip', [
            'cdwfs_2026-2027/structure.json' => '{"meta":{}}',
        ]);
        $manager = $this->createManager();
        $pendingUpload = $manager->storePendingUpload($archive);

        $manager->importPendingArchive($pendingUpload['token'], true);

        self::assertFileDoesNotExist($this->dataDir . '/cdwfs_2026-2027/old.json');
        self::assertFileExists($this->dataDir . '/cdwfs_2026-2027/structure.json');
    }

    public function testItDeletesDatasetDirectory(): void
    {
        mkdir($this->dataDir . '/asrc_2026-2027/modules', 0775, true);
        file_put_contents($this->dataDir . '/asrc_2026-2027/modules/module.json', '{}');

        $this->createManager()->deleteDataset('asrc_2026-2027');

        self::assertDirectoryDoesNotExist($this->dataDir . '/asrc_2026-2027');
    }

    public function testItDeletesReadOnlyFiles(): void
    {
        mkdir($this->dataDir . '/eris_2026-2027/modules', 0775, true);
        $path = $this->dataDir . '/eris_2026-2027/modules/module.json';
        file_put_contents($path, '{}');
        chmod($path, 0444);

        $this->createManager()->deleteDataset('eris_2026-2027');

        self::assertDirectoryDoesNotExist($this->dataDir . '/eris_2026-2027');
    }

    /**
     * @param array<string, string> $files
     */
    private function createUploadedZip(string $name, array $files): UploadedFile
    {
        $path = $this->workspace . '/' . bin2hex(random_bytes(6)) . '.zip';
        $zip = new \ZipArchive();

        self::assertTrue($zip->open($path, \ZipArchive::CREATE));

        foreach ($files as $file => $content) {
            $zip->addFromString($file, $content);
        }

        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
    }

    private function createManager(): PromotionJsonArchiveManager
    {
        return new PromotionJsonArchiveManager($this->dataDir, $this->workspace);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());

                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($path);
    }
}
