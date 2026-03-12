<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Context;

use App\Dto\Context\EvaluationSheet;
use App\Service\Context\EvaluationSheetLoader;
use PHPUnit\Framework\TestCase;

final class EvaluationSheetLoaderTest extends TestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 3) . '/Fixtures/evaluation_sheet_loader';
    }

    public function testLoadReturnsEvaluationSheetForValidJson(): void
    {
        $loader = new EvaluationSheetLoader();
        $path = $this->fixturesRoot . '/valid_evaluation.json';

        $sheet = $loader->load('EC01', $path);

        self::assertInstanceOf(EvaluationSheet::class, $sheet);

        self::assertSame('ec01', $sheet->fileCode);
        self::assertSame($path, $sheet->path);

        self::assertSame('EC01', $sheet->evaluationCode());
        self::assertSame('Épreuve certifiante 1', $sheet->title());
        self::assertSame('2025-2026', $sheet->academicYear());
        self::assertSame('RNCP39608', $sheet->rncpCode());
        self::assertSame('BC01', $sheet->blocCode());
        self::assertSame('Conception', $sheet->blocName());

        self::assertSame(1, $sheet->evaluationNumber);
        self::assertSame('Réaliser une première évaluation certifiante.', $sheet->description);

        self::assertSame(
            [
                [
                    'code' => 'C1',
                    'description' => 'Concevoir une architecture',
                ],
                [
                    'code' => 'C2',
                    'description' => 'Développer une application',
                ],
            ],
            $sheet->skills
        );

        self::assertSame(
            [
                [
                    'code' => 'CR1',
                    'title' => 'Critère 1',
                    'description' => 'Description 1',
                    'indicators' => [
                        'Indicateur 1',
                        'Indicateur 2',
                    ],
                ],
                [
                    'code' => 'CR2',
                    'title' => 'Critère 2',
                    'description' => 'Description 2',
                    'indicators' => [
                        'Indicateur A',
                    ],
                ],
                [
                    'code' => 'CR9',
                    'title' => 'Critère orphelin',
                    'description' => 'Ne matche aucune skill',
                    'indicators' => [
                        'Seul',
                    ],
                ],
            ],
            $sheet->criteria
        );

        self::assertSame(
            [
                [
                    'code' => 'C1',
                    'description' => 'Concevoir une architecture',
                    'criteria' => [
                        [
                            'code' => 'CR1',
                            'title' => 'Critère 1',
                            'description' => 'Description 1',
                            'indicators' => [
                                'Indicateur 1',
                                'Indicateur 2',
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'C2',
                    'description' => 'Développer une application',
                    'criteria' => [
                        [
                            'code' => 'CR2',
                            'title' => 'Critère 2',
                            'description' => 'Description 2',
                            'indicators' => [
                                'Indicateur A',
                            ],
                        ],
                    ],
                ],
            ],
            $sheet->skillsWithCriteria
        );

        self::assertSame('Projet', $sheet->format());
        self::assertSame('Individuel', $sheet->delivery());
        self::assertSame('4h', $sheet->totalDuration());

        self::assertSame(
            [
                [
                    'code' => 'E1',
                    'title' => 'Partie 1',
                ],
            ],
            $sheet->examParts()
        );

        self::assertSame(10, $sheet->examThreshold());
        self::assertSame(8, $sheet->compensationMin());
        self::assertSame(6, $sheet->remedialBelow());
        self::assertSame(10, $sheet->blockThreshold());

        self::assertIsArray($sheet->raw);
        self::assertArrayHasKey('meta', $sheet->raw);
        self::assertArrayHasKey('skills', $sheet->raw);
        self::assertArrayHasKey('criteria', $sheet->raw);
    }

    public function testLoadThrowsExceptionWhenFileIsMissing(): void
    {
        $loader = new EvaluationSheetLoader();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fichier évaluation introuvable ou illisible.');

        $loader->load('EC01', $this->fixturesRoot . '/does_not_exist.json');
    }

    public function testLoadThrowsExceptionWhenJsonIsInvalid(): void
    {
        $loader = new EvaluationSheetLoader();
        $path = $this->fixturesRoot . '/invalid_json.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON évaluation invalide.');

        $loader->load('EC01', $path);
    }

    public function testLoadThrowsExceptionWhenMetaIsMissing(): void
    {
        $loader = new EvaluationSheetLoader();
        $path = $this->fixturesRoot . '/missing_meta.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Clé "meta" manquante ou invalide.');

        $loader->load('EC01', $path);
    }

    public function testLoadThrowsExceptionWhenMetaTypeIsInvalid(): void
    {
        $loader = new EvaluationSheetLoader();
        $path = $this->fixturesRoot . '/invalid_type.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('meta.type invalide (attendu: "evaluation").');

        $loader->load('EC01', $path);
    }

    public function testLoadThrowsExceptionWhenRequiredMetaCodeIsMissing(): void
    {
        $loader = new EvaluationSheetLoader();
        $path = $this->fixturesRoot . '/missing_meta_code.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('meta.code manquant.');

        $loader->load('EC01', $path);
    }

    public function testLoadFallsBackToEmptyArraysForInvalidOptionalCollections(): void
    {
        $loader = new EvaluationSheetLoader();
        $path = $this->fixturesRoot . '/partial_invalid_collections.json';

        $sheet = $loader->load(' EC99 ', $path);

        self::assertInstanceOf(EvaluationSheet::class, $sheet);

        self::assertSame('ec99', $sheet->fileCode);
        self::assertSame('EC99', $sheet->evaluationCode());
        self::assertSame('Évaluation partielle', $sheet->title());
        self::assertSame('2025-2026', $sheet->academicYear());

        self::assertSame(7, $sheet->evaluationNumber);
        self::assertSame('', $sheet->description);

        self::assertSame([], $sheet->skills);
        self::assertSame([], $sheet->criteria);
        self::assertSame([], $sheet->skillsWithCriteria);
        self::assertSame([], $sheet->modalities);
        self::assertSame([], $sheet->exam);

        self::assertSame('', $sheet->format());
        self::assertSame('', $sheet->delivery());
        self::assertSame('', $sheet->totalDuration());
        self::assertSame([], $sheet->examParts());
        self::assertSame(0, $sheet->examThreshold());
        self::assertSame(0, $sheet->compensationMin());
        self::assertSame(0, $sheet->remedialBelow());
        self::assertSame(0, $sheet->blockThreshold());
    }
}
