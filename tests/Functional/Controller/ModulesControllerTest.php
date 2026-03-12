<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\FrameworkModulesProvider;
use App\Service\Context\FrameworkSkillsProvider;
use App\Service\Context\FrameworkStructureLoader;
use App\Service\Context\ModuleSheetLoader;
use App\Service\Context\SkillSheetLoader;
use App\Service\Context\SkillSheetResolver;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ModulesControllerTest extends WebTestCase
{
    private string $fixturesRoot;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 2) . '/Fixtures/modules_controller';
    }

    public function testIndexRedirectsToHomeWhenQueryParametersAreMissing(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $client->request('GET', '/matieres');

        self::assertResponseRedirects('/');
    }

    public function testIndexRedirectsToSummaryWhenNoModuleIsAvailable(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $client->request('GET', '/matieres?promotion=empty&year=2025-2026');

        self::assertResponseRedirects('/promotion?promotion=empty&year=2025-2026');
    }

    public function testIndexDisplaysFirstModuleByDefault(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $crawler = $client->request('GET', '/matieres?promotion=cdwfs&year=2025-2026');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h3', 'Bachelor Développeur Web Full Stack');
        self::assertSelectorTextContains('h1', 'Architecture logicielle');
        self::assertSelectorTextContains('body', 'Objectifs');
        self::assertSelectorTextContains('body', 'Prérequis');
        self::assertSelectorTextContains('body', 'Compétences mobilisées');
        self::assertSelectorTextContains('body', 'Plan des cours');
        self::assertSelectorTextContains('body', "Exemples d'Exercices");

        self::assertSelectorTextContains('body', 'Comprendre les principes d’architecture logicielle.');
        self::assertSelectorTextContains('body', 'Connaître les bases de la programmation orientée objet.');
        self::assertSelectorTextContains('body', 'Compétence C01');
        self::assertSelectorTextContains('body', 'Concevoir une architecture robuste');
        self::assertSelectorTextContains('body', 'Identifier les besoins d’architecture');
        self::assertSelectorTextContains('body', 'Justifier les choix techniques');
        self::assertSelectorTextContains('body', 'Introduction');
        self::assertSelectorTextContains('body', 'Patterns');
        self::assertSelectorTextContains('body', 'Étude de cas');

        self::assertGreaterThanOrEqual(1, $crawler->filter('canvas, .chartjs')->count());
    }

    public function testIndexDisplaysRequestedModuleUsingCaseInsensitiveCode(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $crawler = $client->request('GET', '/matieres?promotion=cdwfs&year=2025-2026&code=FM02');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'Symfony avancé');
        self::assertSelectorTextContains('body', 'Approfondir les composants et bonnes pratiques Symfony.');
        self::assertSelectorTextContains('body', 'Avoir déjà développé une application Symfony simple.');
        self::assertSelectorTextContains('body', 'Compétence C02');
        self::assertSelectorTextContains('body', 'Développer une application Symfony maintenable');
        self::assertSelectorTextContains('body', 'Structurer le code proprement');
        self::assertSelectorTextContains('body', 'Respecter les conventions Symfony');
        self::assertSelectorTextContains('body', 'Services et DI');
        self::assertSelectorTextContains('body', 'Refactorisation de contrôleur');

        $links = $crawler->filter('a')->each(
            static fn($node) => (string)$node->attr('href')
        );

        self::assertTrue(
            \count(array_filter(
                $links,
                static fn(string $href): bool => str_contains($href, '/competences?promotion=cdwfs&year=2025-2026&code=bc01c02')
            )) > 0
        );
    }

    private function overrideServicesForDataset(string $fixturesRoot): void
    {
        $scanner = new FrameworkFolderScanner($fixturesRoot);
        $structureLoader = new FrameworkStructureLoader($fixturesRoot);
        $skillLoader = new SkillSheetLoader();
        $moduleLoader = new ModuleSheetLoader();
        $resolver = new SkillSheetResolver();

        $skillsProvider = new FrameworkSkillsProvider(
            $scanner,
            $skillLoader,
            $structureLoader,
            $resolver
        );

        $modulesProvider = new FrameworkModulesProvider(
            $scanner,
            $moduleLoader,
            $skillsProvider
        );

        static::getContainer()->set(FrameworkStructureLoader::class, $structureLoader);
        static::getContainer()->set(FrameworkModulesProvider::class, $modulesProvider);
    }
}
