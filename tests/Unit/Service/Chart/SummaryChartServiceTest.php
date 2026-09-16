<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Chart;

use App\Dto\Context\ResolvedEvaluationSheet;
use App\Service\Chart\SummaryChartService;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Chartjs\Builder\ChartBuilder;

final class SummaryChartServiceTest extends TestCase
{
    public function testModulesPerEvaluationChartUsesResolvedEvaluationFileCodes(): void
    {
        $service = new SummaryChartService(new ChartBuilder());

        $chart = $service->createModulesPerEvaluationChartFromSheets([
            new ResolvedEvaluationSheet(
                fileCode: 'rncp39608-bc04-ec12',
                path: '',
                meta: [
                    'code' => 'RNCP39608-BC04-EC12',
                ],
                evaluationNumber: 12,
                description: '',
                skills: [],
                criteria: [],
                skillsWithCriteria: [],
                modules: [
                    [
                        'code' => 'rncp39608-bc04-fm08',
                        'title' => 'Veille',
                        'blockCode' => 'BC04',
                    ],
                    [
                        'code' => 'rncp39608-bc04-fm07',
                        'title' => 'Anglais',
                        'blockCode' => 'BC04',
                    ],
                ],
                projects: [],
                modalities: [],
                exam: [],
                raw: [],
            ),
        ]);

        $dataset = $chart->getData()['datasets'][0] ?? [];

        self::assertSame(['EC12'], $chart->getData()['labels']);
        self::assertSame([2], $dataset['data'] ?? []);
        self::assertSame(['rncp39608-bc04-ec12'], $dataset['fileCodes'] ?? []);
    }
}
