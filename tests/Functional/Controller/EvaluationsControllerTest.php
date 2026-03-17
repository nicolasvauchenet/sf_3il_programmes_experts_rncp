<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\Loader\EvaluationSheetLoader;
use App\Service\Context\Provider\FrameworkEvaluationsProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EvaluationsControllerTest extends WebTestCase
{
    private string $fixturesRoot;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 2) . '/Fixtures/evaluations_controller';
    }

    public function testIndexRedirectsToHomeWhenQueryParametersAreMissing(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $client->request('GET', '/evaluations');

        self::assertResponseRedirects('/');
    }

    public function testIndexRedirectsToSummaryWhenNoEvaluationIsAvailable(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $client->request('GET', '/evaluations?promotion=empty&year=2025-2026');

        self::assertResponseRedirects('/promotion?promotion=empty&year=2025-2026');
    }

    public function testIndexDisplaysFirstEvaluationByDefault(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $crawler = $client->request('GET', '/evaluations?promotion=cdwfs&year=2025-2026');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'Épreuve certifiante 1');
        self::assertSelectorTextContains('body', 'Première évaluation centrée sur l’analyse et la conception.');
        self::assertSelectorTextContains('body', 'Modalités');
        self::assertSelectorTextContains('body', 'Format :');
        self::assertSelectorTextContains('body', 'Projet');
        self::assertSelectorTextContains('body', 'Déroulé :');
        self::assertSelectorTextContains('body', 'Individuel');

        self::assertSelectorTextContains('body', 'Épreuves');
        self::assertSelectorTextContains('body', 'EC01.1');
        self::assertSelectorTextContains('body', 'Analyse du besoin');
        self::assertSelectorTextContains('body', 'Type :');
        self::assertSelectorTextContains('body', 'Étude');
        self::assertSelectorTextContains('body', 'Coefficient :');
        self::assertSelectorTextContains('body', '1');
        self::assertSelectorTextContains('body', 'Points :');
        self::assertSelectorTextContains('body', '20');
        self::assertSelectorTextContains('body', 'Durée :');
        self::assertSelectorTextContains('body', '2h');

        self::assertSelectorTextContains('body', 'Compétences mobilisées');
        self::assertSelectorTextContains('body', 'Compétence C01');
        self::assertSelectorTextContains('body', 'Concevoir une architecture');
        self::assertSelectorTextContains('body', 'Critère Cr01');
        self::assertSelectorTextContains('body', 'Identifier les besoins');
        self::assertSelectorTextContains('body', 'Justifier les choix');

        self::assertSelectorTextContains('body', 'Règles de validation');
        self::assertSelectorTextContains('body', 'Validation de l’évaluation :');
        self::assertSelectorTextContains('body', '10');
        self::assertSelectorTextContains('body', 'Compensation possible à partir de :');
        self::assertSelectorTextContains('body', '8');
        self::assertSelectorTextContains('body', 'Rattrapage obligatoire en dessous de :');
        self::assertSelectorTextContains('body', '6');
        self::assertSelectorTextContains('body', 'Validation du bloc :');
        self::assertSelectorTextContains('body', '10');

        self::assertGreaterThanOrEqual(1, $crawler->filter('canvas, .chartjs')->count());
    }

    public function testIndexDisplaysRequestedEvaluationUsingCaseInsensitiveCode(): void
    {
        $client = static::createClient();

        $this->overrideServicesForDataset($this->fixturesRoot);

        $crawler = $client->request('GET', '/evaluations?promotion=cdwfs&year=2025-2026&code=bc01ec02');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'Épreuve certifiante 2');
        self::assertSelectorTextContains('body', 'Deuxième évaluation orientée développement Symfony.');
        self::assertSelectorTextContains('body', 'Étude de cas');
        self::assertSelectorTextContains('body', 'Collectif');
        self::assertSelectorTextContains('body', '6h');

        self::assertSelectorTextContains('body', 'EC02.1');
        self::assertSelectorTextContains('body', 'Production technique');
        self::assertSelectorTextContains('body', 'Projet');
        self::assertSelectorTextContains('body', '40');

        self::assertSelectorTextContains('body', 'Compétence C02');
        self::assertSelectorTextContains('body', 'Développer une application Symfony');
        self::assertSelectorTextContains('body', 'Critère Cr02');
        self::assertSelectorTextContains('body', 'Structurer le code');
        self::assertSelectorTextContains('body', 'Respecter les conventions Symfony');

        self::assertSelectorTextContains('body', '12');
        self::assertSelectorTextContains('body', '9');
        self::assertSelectorTextContains('body', '7');
        self::assertSelectorTextContains('body', '10');

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
        $loader = new EvaluationSheetLoader();

        $provider = new FrameworkEvaluationsProvider(
            $scanner,
            $loader
        );

        static::getContainer()->set(FrameworkEvaluationsProvider::class, $provider);
    }
}
