<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Context;

use App\Dto\Context\EvaluationSheet;
use App\Service\Context\EvaluationSheetLoader;
use App\Service\Context\FrameworkEvaluationsProvider;
use App\Service\Context\FrameworkFolderScanner;
use PHPUnit\Framework\TestCase;

final class FrameworkEvaluationsProviderTest extends TestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 3) . '/Fixtures/framework_evaluations_provider';
    }

    public function testListEvaluationsReturnsSortedEvaluationsAndIgnoresInvalidFiles(): void
    {
        $scanner = new FrameworkFolderScanner($this->fixturesRoot);
        $loader = new EvaluationSheetLoader();

        $provider = new FrameworkEvaluationsProvider($scanner, $loader);

        $evaluations = $provider->listEvaluations('cdwfs', '2025-2026');

        self::assertCount(2, $evaluations);

        self::assertInstanceOf(EvaluationSheet::class, $evaluations[0]);
        self::assertInstanceOf(EvaluationSheet::class, $evaluations[1]);

        self::assertSame('ec01', $evaluations[0]->fileCode);
        self::assertSame('EC01', $evaluations[0]->evaluationCode());
        self::assertSame('Épreuve certifiante 1', $evaluations[0]->title());
        self::assertSame('2025-2026', $evaluations[0]->academicYear());
        self::assertSame('RNCP39608', $evaluations[0]->rncpCode());
        self::assertSame('BC01', $evaluations[0]->blocCode());
        self::assertSame('Conception', $evaluations[0]->blocName());
        self::assertSame(1, $evaluations[0]->evaluationNumber);
        self::assertSame('Réaliser une première évaluation certifiante.', $evaluations[0]->description);

        self::assertSame(
            [
                [
                    'code' => 'C01',
                    'description' => 'Concevoir une architecture',
                ],
            ],
            $evaluations[0]->skills
        );

        self::assertSame(
            [
                [
                    'code' => 'Cr01',
                    'title' => 'Critère 1',
                    'description' => 'Description 1',
                    'indicators' => [
                        'Indicateur 1',
                        'Indicateur 2',
                    ],
                ],
            ],
            $evaluations[0]->criteria
        );

        self::assertSame(
            [
                [
                    'code' => 'C01',
                    'description' => 'Concevoir une architecture',
                    'criteria' => [
                        [
                            'code' => 'Cr01',
                            'title' => 'Critère 1',
                            'description' => 'Description 1',
                            'indicators' => [
                                'Indicateur 1',
                                'Indicateur 2',
                            ],
                        ],
                    ],
                ],
            ],
            $evaluations[0]->skillsWithCriteria
        );

        self::assertSame('Projet', $evaluations[0]->format());
        self::assertSame('Individuel', $evaluations[0]->delivery());
        self::assertSame('4h', $evaluations[0]->totalDuration());
        self::assertSame(
            [
                [
                    'code' => 'EC01',
                    'title' => 'Partie 1',
                ],
            ],
            $evaluations[0]->examParts()
        );
        self::assertSame(10, $evaluations[0]->examThreshold());
        self::assertSame(8, $evaluations[0]->compensationMin());
        self::assertSame(6, $evaluations[0]->remedialBelow());
        self::assertSame(10, $evaluations[0]->blockThreshold());

        self::assertSame('ec02', $evaluations[1]->fileCode);
        self::assertSame('EC02', $evaluations[1]->evaluationCode());
        self::assertSame('Épreuve certifiante 2', $evaluations[1]->title());
        self::assertSame('2025-2026', $evaluations[1]->academicYear());
        self::assertSame('RNCP39608', $evaluations[1]->rncpCode());
        self::assertSame('BC02', $evaluations[1]->blocCode());
        self::assertSame('Développement', $evaluations[1]->blocName());
        self::assertSame(2, $evaluations[1]->evaluationNumber);
        self::assertSame('Deuxième évaluation.', $evaluations[1]->description);

        self::assertSame(
            [
                [
                    'code' => 'C02',
                    'description' => 'Développer une application',
                ],
                [
                    'code' => 'C09',
                    'description' => 'Compétence inconnue',
                ],
            ],
            $evaluations[1]->skills
        );

        self::assertSame(
            [
                [
                    'code' => 'Cr02',
                    'title' => 'Critère 2',
                    'description' => 'Description 2',
                    'indicators' => [
                        'Indicateur A',
                    ],
                ],
                [
                    'code' => 'Cr09',
                    'title' => 'Critère 9',
                    'description' => 'Description 9',
                    'indicators' => [
                        'Indicateur Z',
                    ],
                ],
            ],
            $evaluations[1]->criteria
        );

        self::assertSame(
            [
                [
                    'code' => 'C02',
                    'description' => 'Développer une application',
                    'criteria' => [
                        [
                            'code' => 'Cr02',
                            'title' => 'Critère 2',
                            'description' => 'Description 2',
                            'indicators' => [
                                'Indicateur A',
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'C09',
                    'description' => 'Compétence inconnue',
                    'criteria' => [
                        [
                            'code' => 'Cr09',
                            'title' => 'Critère 9',
                            'description' => 'Description 9',
                            'indicators' => [
                                'Indicateur Z',
                            ],
                        ],
                    ],
                ],
            ],
            $evaluations[1]->skillsWithCriteria
        );

        self::assertSame('Étude de cas', $evaluations[1]->format());
        self::assertSame('Collectif', $evaluations[1]->delivery());
        self::assertSame('6h', $evaluations[1]->totalDuration());
        self::assertSame(
            [
                [
                    'code' => 'EC01',
                    'title' => 'Analyse',
                ],
                [
                    'code' => 'EC02',
                    'title' => 'Production',
                ],
            ],
            $evaluations[1]->examParts()
        );
        self::assertSame(12, $evaluations[1]->examThreshold());
        self::assertSame(9, $evaluations[1]->compensationMin());
        self::assertSame(7, $evaluations[1]->remedialBelow());
        self::assertSame(10, $evaluations[1]->blockThreshold());
    }
}
