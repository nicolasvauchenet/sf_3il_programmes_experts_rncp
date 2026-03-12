<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Context;

use App\Dto\Context\ModuleReference;
use App\Service\Context\FrameworkFolderScanner;
use PHPUnit\Framework\TestCase;

final class FrameworkFolderScannerTest extends TestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 3) . '/Fixtures/framework_scanner';
    }

    public function testListJsonFilesReturnsSortedModuleReferencesFromTopLevelFolderOnly(): void
    {
        $scanner = new FrameworkFolderScanner($this->fixturesRoot);

        $refs = $scanner->listJsonFiles('CDWFS', '2025-2026', 'modules');

        self::assertCount(2, $refs);

        foreach ($refs as $ref) {
            self::assertInstanceOf(ModuleReference::class, $ref);
        }

        self::assertSame('cdwfs_2025-2026', $refs[0]->datasetCode);
        self::assertSame('modules', $refs[0]->folderName);
        self::assertSame('fm01', $refs[0]->fileCode);
        self::assertStringEndsWith(
            '/tests/Fixtures/framework_scanner/cdwfs_2025-2026/modules/fm01.json',
            $this->normalizePath($refs[0]->path)
        );

        self::assertSame('cdwfs_2025-2026', $refs[1]->datasetCode);
        self::assertSame('modules', $refs[1]->folderName);
        self::assertSame('fm02', $refs[1]->fileCode);
        self::assertStringEndsWith(
            '/tests/Fixtures/framework_scanner/cdwfs_2025-2026/modules/fm02.json',
            $this->normalizePath($refs[1]->path)
        );
    }

    public function testListJsonFilesIgnoresUnknownFolder(): void
    {
        $scanner = new FrameworkFolderScanner($this->fixturesRoot);

        $refs = $scanner->listJsonFiles('cdwfs', '2025-2026', 'skills');

        self::assertSame([], $refs);
    }

    public function testListJsonFilesReturnsEmptyArrayWhenArgumentsAreBlank(): void
    {
        $scanner = new FrameworkFolderScanner($this->fixturesRoot);

        self::assertSame([], $scanner->listJsonFiles('', '2025-2026', 'modules'));
        self::assertSame([], $scanner->listJsonFiles('cdwfs', '', 'modules'));
        self::assertSame([], $scanner->listJsonFiles('cdwfs', '2025-2026', ''));
    }

    public function testListJsonFilesTrimsPromotionYearAndFolderName(): void
    {
        $scanner = new FrameworkFolderScanner($this->fixturesRoot);

        $refs = $scanner->listJsonFiles('  CDWFS  ', ' 2025-2026 ', ' /modules/ ');

        self::assertCount(2, $refs);
        self::assertSame('cdwfs_2025-2026', $refs[0]->datasetCode);
        self::assertSame('modules', $refs[0]->folderName);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
