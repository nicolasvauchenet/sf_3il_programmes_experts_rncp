<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Context;

use App\Dto\Context\ResolvedSkillSheet;
use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\Loader\FrameworkStructureLoader;
use App\Service\Context\Loader\SkillSheetLoader;
use App\Service\Context\Provider\FrameworkSkillsProvider;
use App\Service\Context\SkillSheetResolver;
use PHPUnit\Framework\TestCase;

final class FrameworkSkillsProviderTest extends TestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 3) . '/Fixtures/framework_skills_provider';
    }

    public function testListSkillsReturnsSortedResolvedSkillsAndIgnoresInvalidFiles(): void
    {
        $scanner = new FrameworkFolderScanner($this->fixturesRoot);
        $loader = new SkillSheetLoader();
        $structureLoader = new FrameworkStructureLoader($this->fixturesRoot);
        $resolver = new SkillSheetResolver();

        $provider = new FrameworkSkillsProvider(
            $scanner,
            $loader,
            $structureLoader,
            $resolver,
        );

        $skills = $provider->listSkills('cdwfs', '2025-2026');

        self::assertCount(2, $skills);

        self::assertInstanceOf(ResolvedSkillSheet::class, $skills[0]);
        self::assertInstanceOf(ResolvedSkillSheet::class, $skills[1]);

        self::assertSame('bc01c01', $skills[0]->fileCode);
        self::assertSame('C01', $skills[0]->skillCode());
        self::assertSame('RNCP39608-BC01-C01', $skills[0]->fullSkillCode());
        self::assertSame('Skill C01', $skills[0]->title());
        self::assertSame('2025-2026', $skills[0]->academicYear());
        self::assertSame('BC01', $skills[0]->blocCode());
        self::assertSame('Conception', $skills[0]->blocName());
        self::assertSame('RNCP39608', $skills[0]->rncpCode);
        self::assertSame('Description de C01', $skills[0]->description);
        self::assertSame(['Critère Cr01 - A', 'Critère Cr01 - B'], $skills[0]->criteria);

        self::assertSame(
            [
                [
                    'code' => 'FM01',
                    'title' => 'Module 1',
                    'fullCode' => 'RNCP39608-BC01-FM01',
                ],
            ],
            $skills[0]->modules
        );

        self::assertSame(
            [
                [
                    'code' => 'EC01',
                    'blockCode' => 'BC01',
                ],
            ],
            $skills[0]->evaluations
        );

        self::assertSame(
            [
                [
                    'fileCode' => 'bc01c02',
                    'code' => 'C02',
                    'title' => 'Compétence 2',
                ],
                [
                    'fileCode' => 'c03',
                    'code' => 'C03',
                    'title' => 'Compétence 3 non chargée',
                ],
            ],
            $skills[0]->relatedSkills
        );

        self::assertSame('bc01c02', $skills[1]->fileCode);
        self::assertSame('C02', $skills[1]->skillCode());
        self::assertSame('RNCP39608-BC01-C02', $skills[1]->fullSkillCode());
        self::assertSame('Skill C02', $skills[1]->title());
        self::assertSame('2025-2026', $skills[1]->academicYear());
        self::assertSame('BC01', $skills[1]->blocCode());
        self::assertSame('Conception', $skills[1]->blocName());
        self::assertSame('RNCP39608', $skills[1]->rncpCode);
        self::assertSame('Description de C02', $skills[1]->description);
        self::assertSame(['Critère Cr02 - A'], $skills[1]->criteria);

        self::assertSame(
            [
                [
                    'code' => 'FM02',
                    'title' => 'Module 2',
                    'fullCode' => 'RNCP39608-BC01-FM02',
                ],
            ],
            $skills[1]->modules
        );

        self::assertSame(
            [
                [
                    'code' => 'EC02',
                    'blockCode' => 'BC01',
                ],
            ],
            $skills[1]->evaluations
        );

        self::assertSame(
            [
                [
                    'fileCode' => 'bc01c01',
                    'code' => 'C01',
                    'title' => 'Compétence 1',
                ],
                [
                    'fileCode' => 'c03',
                    'code' => 'C03',
                    'title' => 'Compétence 3 non chargée',
                ],
            ],
            $skills[1]->relatedSkills
        );
    }
}
