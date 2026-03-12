<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Context;

use App\Dto\Context\ModuleSheet;
use App\Service\Context\ModuleSheetLoader;
use PHPUnit\Framework\TestCase;

final class ModuleSheetLoaderTest extends TestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 3) . '/Fixtures/module_sheet_loader';
    }

    public function testLoadReturnsModuleSheetForValidJson(): void
    {
        $loader = new ModuleSheetLoader();
        $path = $this->fixturesRoot . '/valid_module.json';

        $sheet = $loader->load('FM01', $path);

        self::assertInstanceOf(ModuleSheet::class, $sheet);

        self::assertSame('fm01', $sheet->fileCode);
        self::assertSame($path, $sheet->path);

        self::assertSame('FM01', $sheet->moduleCode());
        self::assertSame('Architecture logicielle', $sheet->title());
        self::assertSame('2025-2026', $sheet->academicYear());
        self::assertSame('BC01', $sheet->blocCode());
        self::assertSame('Conception', $sheet->blocName());
        self::assertSame(5, $sheet->durationDays());
        self::assertSame(35, $sheet->durationHours());

        self::assertSame(
            [
                [
                    'code' => 'C1',
                    'description' => 'Modéliser une architecture',
                ],
                [
                    'code' => 'C2',
                    'description' => 'Concevoir une solution logicielle',
                ],
            ],
            $sheet->skills
        );

        self::assertSame([], $sheet->skillsWithCriteria);

        self::assertSame(['summary' => 'Comprendre les bases'], $sheet->objectives);
        self::assertSame(['level' => 'Intermédiaire'], $sheet->prerequisites);
        self::assertSame(
            [
                'chapters' => [
                    ['title' => 'Intro'],
                ],
            ],
            $sheet->outline
        );

        self::assertSame(
            [
                ['title' => 'Exercice 1'],
            ],
            $sheet->exercises
        );

        self::assertSame(
            [
                ['title' => 'Clean Architecture'],
            ],
            $sheet->bibliography
        );

        self::assertSame(
            [
                'Cours magistral',
                'Atelier pratique',
            ],
            $sheet->teachingMethods
        );

        self::assertIsArray($sheet->raw);
        self::assertArrayHasKey('meta', $sheet->raw);
        self::assertArrayHasKey('skills', $sheet->raw);
    }

    public function testLoadThrowsExceptionWhenFileIsMissing(): void
    {
        $loader = new ModuleSheetLoader();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fichier module introuvable ou illisible.');

        $loader->load('FM01', $this->fixturesRoot . '/does_not_exist.json');
    }

    public function testLoadThrowsExceptionWhenJsonIsInvalid(): void
    {
        $loader = new ModuleSheetLoader();
        $path = $this->fixturesRoot . '/invalid_json.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON module invalide.');

        $loader->load('FM01', $path);
    }

    public function testLoadThrowsExceptionWhenMetaIsMissing(): void
    {
        $loader = new ModuleSheetLoader();
        $path = $this->fixturesRoot . '/missing_meta.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Clé "meta" manquante ou invalide.');

        $loader->load('FM01', $path);
    }

    public function testLoadThrowsExceptionWhenMetaTypeIsInvalid(): void
    {
        $loader = new ModuleSheetLoader();
        $path = $this->fixturesRoot . '/invalid_type.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('meta.type invalide (attendu: "module").');

        $loader->load('FM01', $path);
    }

    public function testLoadThrowsExceptionWhenRequiredMetaCodeIsMissing(): void
    {
        $loader = new ModuleSheetLoader();
        $path = $this->fixturesRoot . '/missing_meta_code.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('meta.code manquant.');

        $loader->load('FM01', $path);
    }

    public function testLoadFallsBackToEmptyArraysForInvalidOptionalCollections(): void
    {
        $loader = new ModuleSheetLoader();
        $path = $this->fixturesRoot . '/partial_invalid_collections.json';

        $sheet = $loader->load(' FM99 ', $path);

        self::assertInstanceOf(ModuleSheet::class, $sheet);

        self::assertSame('fm99', $sheet->fileCode);
        self::assertSame('FM99', $sheet->moduleCode());
        self::assertSame('Module partiel', $sheet->title());
        self::assertSame('2025-2026', $sheet->academicYear());

        self::assertSame([], $sheet->skills);
        self::assertSame([], $sheet->skillsWithCriteria);
        self::assertSame([], $sheet->objectives);
        self::assertSame([], $sheet->prerequisites);
        self::assertSame([], $sheet->outline);
        self::assertSame([], $sheet->exercises);
        self::assertSame([], $sheet->bibliography);
        self::assertSame([], $sheet->teachingMethods);
    }
}
