<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Context;

use App\Dto\Context\ModuleSheet;
use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\Loader\FrameworkStructureLoader;
use App\Service\Context\Loader\ModuleSheetLoader;
use App\Service\Context\Loader\SkillSheetLoader;
use App\Service\Context\Provider\FrameworkModulesProvider;
use App\Service\Context\Provider\FrameworkSkillsProvider;
use App\Service\Context\SkillSheetResolver;
use PHPUnit\Framework\TestCase;

final class FrameworkModulesProviderTest extends TestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 3) . '/Fixtures/framework_modules_provider';
    }

    public function testListModulesReturnsSortedEnrichedModulesAndIgnoresInvalidFiles(): void
    {
        $scanner = new FrameworkFolderScanner($this->fixturesRoot);
        $moduleLoader = new ModuleSheetLoader();
        $skillLoader = new SkillSheetLoader();
        $structureLoader = new FrameworkStructureLoader($this->fixturesRoot);
        $resolver = new SkillSheetResolver();

        $skillsProvider = new FrameworkSkillsProvider(
            $scanner,
            $skillLoader,
            $structureLoader,
            $resolver,
        );

        $provider = new FrameworkModulesProvider(
            $scanner,
            $moduleLoader,
            $skillsProvider,
        );

        $modules = $provider->listModules('cdwfs', '2025-2026');

        self::assertCount(2, $modules);

        self::assertInstanceOf(ModuleSheet::class, $modules[0]);
        self::assertInstanceOf(ModuleSheet::class, $modules[1]);

        self::assertSame('fm01', $modules[0]->fileCode);
        self::assertSame('FM01', $modules[0]->moduleCode());
        self::assertSame('Module BC01', $modules[0]->title());
        self::assertSame('BC01', $modules[0]->blocCode());

        self::assertSame(
            [
                [
                    'code' => 'C01',
                    'description' => 'Compétence commune',
                    'criteria' => [
                        'Critère BC01 - A',
                        'Critère BC01 - B',
                    ],
                ],
            ],
            $modules[0]->skillsWithCriteria
        );

        self::assertSame('fm02', $modules[1]->fileCode);
        self::assertSame('FM02', $modules[1]->moduleCode());
        self::assertSame('Module BC02', $modules[1]->title());
        self::assertSame('BC02', $modules[1]->blocCode());

        self::assertSame(
            [
                [
                    'code' => 'C01',
                    'description' => 'Compétence commune',
                    'criteria' => [
                        'Critère BC02 - A',
                    ],
                ],
                [
                    'code' => 'C09',
                    'description' => 'Compétence inconnue',
                    'criteria' => [],
                ],
            ],
            $modules[1]->skillsWithCriteria
        );
    }
}
