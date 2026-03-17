<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Context;

use App\Dto\Context\FrameworkStructure;
use App\Service\Context\Loader\FrameworkStructureLoader;
use PHPUnit\Framework\TestCase;

final class FrameworkStructureLoaderTest extends TestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 3) . '/Fixtures/framework_structure_loader';
    }

    public function testLoadReturnsFrameworkStructureForValidStructureFile(): void
    {
        $loader = new FrameworkStructureLoader($this->fixturesRoot);

        $structure = $loader->load('CDWFS', '2025-2026');

        self::assertInstanceOf(FrameworkStructure::class, $structure);

        self::assertSame('cdwfs_2025-2026', $structure->datasetCode());
        self::assertSame('Bachelor Développeur Web Full Stack', $structure->certificationName());

        self::assertSame(
            [
                'datasetCode' => 'cdwfs_2025-2026',
                'certificationName' => 'Bachelor Développeur Web Full Stack',
                'rncpCode' => '39608',
                'academicYear' => '2025-2026',
            ],
            $structure->meta
        );

        self::assertCount(1, $structure->blocks);
        self::assertCount(1, $structure->modules);
        self::assertCount(1, $structure->skills);
        self::assertCount(1, $structure->evaluations);

        self::assertSame('BC01', $structure->blocks[0]['code']);
        self::assertSame('FM01', $structure->modules[0]['code']);
        self::assertSame('C1', $structure->skills[0]['code']);
        self::assertSame('EC01', $structure->evaluations[0]['code']);

        self::assertIsArray($structure->raw);
        self::assertArrayHasKey('meta', $structure->raw);
        self::assertArrayHasKey('modules', $structure->raw);
    }

    public function testLoadThrowsExceptionWhenStructureFileIsMissing(): void
    {
        $loader = new FrameworkStructureLoader($this->fixturesRoot);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('structure.json introuvable pour "unknown_2025-2026".');

        $loader->load('unknown', '2025-2026');
    }

    public function testLoadThrowsExceptionWhenJsonIsInvalid(): void
    {
        $loader = new FrameworkStructureLoader($this->fixturesRoot);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON invalide pour "invalid_json_2025-2026".');

        $loader->load('invalid_json', '2025-2026');
    }

    public function testLoadThrowsExceptionWhenMetaKeyIsMissing(): void
    {
        $loader = new FrameworkStructureLoader($this->fixturesRoot);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Clé "meta" manquante pour "missing_meta_2025-2026".');

        $loader->load('missing_meta', '2025-2026');
    }

    public function testLoadFallsBackToEmptyArraysWhenOptionalCollectionsAreMissingOrInvalid(): void
    {
        $loader = new FrameworkStructureLoader($this->fixturesRoot);

        $structure = $loader->load('partial', '2025-2026');

        self::assertInstanceOf(FrameworkStructure::class, $structure);

        self::assertSame('partial_2025-2026', $structure->datasetCode());
        self::assertSame('Jeu de test partiel', $structure->certificationName());

        self::assertSame([], $structure->blocks);
        self::assertSame([], $structure->modules);
        self::assertSame([], $structure->skills);
        self::assertSame([], $structure->evaluations);

        self::assertIsArray($structure->raw);
        self::assertArrayHasKey('meta', $structure->raw);
    }
}
