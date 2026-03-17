<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Service\Context\Loader\FrameworkStructureLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SummaryControllerTest extends WebTestCase
{
    private string $fixturesRoot;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 2) . '/Fixtures/summary_controller';
    }

    public function testIndexRedirectsToHomeWhenQueryParametersAreMissing(): void
    {
        $client = static::createClient();

        static::getContainer()->set(
            FrameworkStructureLoader::class,
            new FrameworkStructureLoader($this->fixturesRoot)
        );

        $client->request('GET', '/promotion');

        self::assertResponseRedirects('/');
    }

    public function testIndexRedirectsToHomeWhenStructureIsInvalid(): void
    {
        $client = static::createClient();

        static::getContainer()->set(
            FrameworkStructureLoader::class,
            new FrameworkStructureLoader($this->fixturesRoot)
        );

        $client->request('GET', '/promotion?promotion=broken&year=2025-2026');

        self::assertResponseRedirects('/');
    }

    public function testIndexDisplaysSummaryDashboardWhenStructureIsValid(): void
    {
        $client = static::createClient();

        static::getContainer()->set(
            FrameworkStructureLoader::class,
            new FrameworkStructureLoader($this->fixturesRoot)
        );

        $crawler = $client->request('GET', '/promotion?promotion=cdwfs&year=2025-2026');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'Bachelor Développeur Web Full Stack');
        self::assertSelectorTextContains(
            '.subtitle',
            'Tableau de bord du référentiel'
        );

        $badges = $crawler->filter('.ribbon .app-badge')->each(
            static fn($node) => trim($node->text())
        );

        self::assertContains('RNCP39608', $badges);
        self::assertContains('CDWFS', $badges);
        self::assertContains('2025-2026', $badges);

        self::assertSelectorTextContains('h2', 'Structure du référentiel');
        self::assertSelectorTextContains('body', 'Téléchargements');
        self::assertSelectorTextContains('body', 'Répartition des matières par bloc');
        self::assertSelectorTextContains('body', 'Répartition des compétences par bloc');
        self::assertSelectorTextContains('body', 'Répartition des évaluations par bloc');
        self::assertSelectorTextContains('body', 'Répartition des matières par Évaluation (EC)');
        self::assertSelectorTextContains('body', 'Répartition des compétences par Évaluation (EC)');

        $downloadLinks = $crawler->filter('a.app-button.download')->each(
            static fn($node) => [
                'text' => trim($node->text()),
                'href' => $node->attr('href'),
                'target' => $node->attr('target'),
            ]
        );

        self::assertCount(3, $downloadLinks);

        self::assertSame('Référentiel RNCP', $downloadLinks[0]['text']);
        self::assertStringEndsWith(
            '/source/cdwfs_2025-2026/referentiel-rncp.pdf',
            (string)$downloadLinks[0]['href']
        );
        self::assertSame('_blank', $downloadLinks[0]['target']);

        self::assertSame('Matrice de couverture', $downloadLinks[1]['text']);
        self::assertStringEndsWith(
            '/source/cdwfs_2025-2026/matrice-de-couverture.xlsx',
            (string)$downloadLinks[1]['href']
        );
        self::assertSame('_blank', $downloadLinks[1]['target']);

        self::assertSame('Règlement des examens', $downloadLinks[2]['text']);
        self::assertStringEndsWith(
            '/source/cdwfs_2025-2026/reglement-des-examens.pdf',
            (string)$downloadLinks[2]['href']
        );
        self::assertSame('_blank', $downloadLinks[2]['target']);

        self::assertGreaterThanOrEqual(6, $crawler->filter('canvas')->count());
    }
}
