<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Service\Context\Provider\AvailableFrameworksProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 2) . '/Fixtures/home_controller';
    }

    public function testIndexDisplaysAvailablePromotionsAndYears(): void
    {
        $client = static::createClient();

        static::getContainer()->set(
            AvailableFrameworksProvider::class,
            new AvailableFrameworksProvider($this->fixturesRoot)
        );

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'Choisissez le référentiel');
        self::assertSelectorTextContains('.subtitle', 'Sélectionnez la promotion et le millésime pour continuer.');

        self::assertSelectorExists('form.app-form');
        self::assertSelectorExists('select#promotion');
        self::assertSelectorExists('select#year');
        self::assertSelectorExists('button[type="submit"][disabled]');

        $promotionOptions = $crawler->filter('select#promotion option')->each(
            static fn($node) => trim($node->text())
        );

        self::assertSame(
            [
                'Choisissez une Promotion',
                'CDWFS',
                'EADL',
            ],
            $promotionOptions
        );

        $yearOptions = $crawler->filter('select#year option')->each(
            static fn($node) => trim($node->text())
        );

        self::assertSame(
            [
                'Choisissez un Millésime',
                '2025 - 2026',
                '2024 - 2025',
            ],
            $yearOptions
        );

        $formAction = $crawler->filter('form.app-form')->attr('action');
        self::assertNotNull($formAction);
        self::assertStringContainsString('/promotion', $formAction);
    }

    public function testIndexDisplaysEmptySelectorsWhenNoFrameworkIsAvailable(): void
    {
        $client = static::createClient();

        $emptyDir = sys_get_temp_dir() . '/home_controller_empty_' . uniqid('', true);
        mkdir($emptyDir, 0777, true);

        static::getContainer()->set(
            AvailableFrameworksProvider::class,
            new AvailableFrameworksProvider($emptyDir)
        );

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();

        $promotionOptions = $crawler->filter('select#promotion option')->each(
            static fn($node) => trim($node->text())
        );

        self::assertSame(
            [
                'Choisissez une Promotion',
            ],
            $promotionOptions
        );

        $yearOptions = $crawler->filter('select#year option')->each(
            static fn($node) => trim($node->text())
        );

        self::assertSame(
            [
                'Choisissez un Millésime',
            ],
            $yearOptions
        );
    }
}
