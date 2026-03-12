<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\FrameworkSkillsProvider;
use App\Service\Context\FrameworkStructureLoader;
use App\Service\Context\SkillSheetLoader;
use App\Service\Context\SkillSheetResolver;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SkillsControllerTest extends WebTestCase
{
    private string $fixturesRoot;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 2) . '/Fixtures/skills_controller';
    }

    public function testIndexRedirectsToHomeWhenQueryParametersAreMissing(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $client->request('GET', '/competences');

        self::assertResponseRedirects('/');
    }

    public function testIndexRedirectsToSummaryWhenNoSkillIsAvailable(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $client->request('GET', '/competences?promotion=empty&year=2025-2026');

        self::assertResponseRedirects('/promotion?promotion=empty&year=2025-2026');
    }

    public function testIndexDisplaysFirstSkillByDefault(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $crawler = $client->request('GET', '/competences?promotion=cdwfs&year=2025-2026');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h3', 'Bachelor Développeur Web Full Stack');
        self::assertSelectorTextContains('h1', 'Concevoir une architecture');
        self::assertSelectorTextContains('body', 'Définition');
        self::assertSelectorTextContains('body', 'Critères d’évaluation');
        self::assertSelectorTextContains('body', 'Matières associées');
        self::assertSelectorTextContains('body', 'Épreuves certifiantes associées');

        self::assertSelectorTextContains('body', 'Définir une architecture logicielle cohérente et évolutive.');
        self::assertSelectorTextContains('body', 'Critère 1');
        self::assertSelectorTextContains('body', 'Identifier les besoins techniques');
        self::assertSelectorTextContains('body', 'Critère 2');
        self::assertSelectorTextContains('body', 'Justifier les choix d’architecture');

        self::assertSelectorTextContains('body', 'FM01');
        self::assertSelectorTextContains('body', 'Architecture logicielle');
        self::assertSelectorTextContains('body', 'EC01');
        self::assertSelectorTextContains('body', 'Bloc évalué : BC01');

        self::assertGreaterThanOrEqual(1, $crawler->filter('canvas, .chartjs')->count());
    }

    public function testIndexDisplaysRequestedSkillUsingCaseInsensitiveCode(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $crawler = $client->request('GET', '/competences?promotion=cdwfs&year=2025-2026&code=bc01c02');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'Développer une application Symfony');
        self::assertSelectorTextContains('body', 'Construire une application Symfony maintenable et testable.');
        self::assertSelectorTextContains('body', 'Critère A');
        self::assertSelectorTextContains('body', 'Structurer correctement le projet');
        self::assertSelectorTextContains('body', 'Critère B');
        self::assertSelectorTextContains('body', 'Respecter les conventions Symfony');
        self::assertSelectorTextContains('body', 'Critère C');
        self::assertSelectorTextContains('body', 'Séparer la logique métier');

        self::assertSelectorTextContains('body', 'FM02');
        self::assertSelectorTextContains('body', 'Symfony avancé');
        self::assertSelectorTextContains('body', 'EC02');
        self::assertSelectorTextContains('body', 'Bloc évalué : BC01');

        $links = $crawler->filter('a')->each(
            static fn($node) => (string)$node->attr('href')
        );

        self::assertTrue(
            \count(array_filter(
                $links,
                static fn(string $href): bool => str_contains($href, '/matieres?promotion=cdwfs&year=2025-2026&code=bc01fm02')
            )) > 0
        );

        self::assertTrue(
            \count(array_filter(
                $links,
                static fn(string $href): bool => str_contains($href, '/evaluations?promotion=cdwfs&year=2025-2026&code=ec02')
            )) > 0
        );
    }

    private function overrideServicesForDataset(string $fixturesRoot): void
    {
        $scanner = new FrameworkFolderScanner($fixturesRoot);
        $structureLoader = new FrameworkStructureLoader($fixturesRoot);
        $skillLoader = new SkillSheetLoader();
        $resolver = new SkillSheetResolver();

        $skillsProvider = new FrameworkSkillsProvider(
            $scanner,
            $skillLoader,
            $structureLoader,
            $resolver
        );

        static::getContainer()->set(FrameworkStructureLoader::class, $structureLoader);
        static::getContainer()->set(FrameworkSkillsProvider::class, $skillsProvider);
    }
}
