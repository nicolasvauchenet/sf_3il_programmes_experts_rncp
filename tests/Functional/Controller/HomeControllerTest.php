<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Framework;
use App\Entity\Promotion;
use App\Entity\User;
use App\Enum\Program;
use App\Repository\PromotionRepository;
use App\Service\Context\Provider\AvailableFrameworksProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    /**
     * @var User[]
     */
    private array $createdUsers = [];

    protected function tearDown(): void
    {
        if (self::$booted) {
            $entityManager = static::getContainer()->get(EntityManagerInterface::class);

            foreach ($this->createdUsers as $user) {
                if ($entityManager->contains($user)) {
                    $entityManager->remove($user);
                }
            }

            $entityManager->flush();
        }

        $this->createdUsers = [];

        parent::tearDown();
    }

    public function testIndexDisplaysAvailablePromotionsAndYears(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->setAvailablePromotions([
            $this->createPromotion(Program::CDWFS, '2024-2025'),
            $this->createPromotion(Program::CDWFS, '2025-2026'),
            $this->createPromotion(Program::EADL, '2025-2026'),
        ]);

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'Choisissez le référentiel');
        self::assertSelectorTextContains('.subtitle', 'Sélectionnez la promotion et le millésime pour continuer.');

        self::assertSelectorExists('form.app-form');
        self::assertSelectorExists('select#promotion');
        self::assertSelectorExists('select#year');
        self::assertSelectorExists('button[type="submit"][disabled]');
        self::assertSelectorExists('form[data-context-auto-select-latest-year-value="false"]');

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
        $client = $this->createAuthenticatedClient();

        $this->setAvailablePromotions([]);

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

    public function testStudentCannotChooseAcademicYear(): void
    {
        $client = $this->createAuthenticatedClient(['ROLE_STUDENT']);

        $this->setAvailablePromotions([
            $this->createPromotion(Program::CDWFS, '2024-2025'),
            $this->createPromotion(Program::CDWFS, '2025-2026'),
            $this->createPromotion(Program::EADL, '2025-2026'),
        ]);

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[data-context-auto-select-latest-year-value="true"]');
        self::assertSelectorExists('select#year');
        self::assertSelectorExists('select#year option[value="2025-2026"][selected]');
        self::assertSelectorNotExists('select#year option[value="2024-2025"]');

        self::assertSelectorExists('.form-group[hidden] select#year');

        $yearsByPromotion = json_decode(
            (string)$crawler->filter('form.app-form')->attr('data-context-years-by-promotion-value'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(['2025-2026'], $yearsByPromotion['cdwfs']);
    }

    /**
     * @param list<string> $roles
     */
    private function createAuthenticatedClient(array $roles = ['ROLE_TEACHER']): KernelBrowser
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = (new User())
            ->setEmail('home-test-' . bin2hex(random_bytes(6)) . '@example.com')
            ->setFullName('Test User')
            ->setRoles($roles)
            ->setPassword('irrelevant');

        $entityManager->persist($user);
        $entityManager->flush();

        $this->createdUsers[] = $user;

        $client->loginUser($user);

        return $client;
    }

    /**
     * @param Promotion[] $promotions
     */
    private function setAvailablePromotions(array $promotions): void
    {
        $repository = $this->createMock(PromotionRepository::class);
        $repository
            ->method('findAll')
            ->willReturn($promotions);

        static::getContainer()->set(
            AvailableFrameworksProvider::class,
            new AvailableFrameworksProvider($repository)
        );
    }

    private function createPromotion(Program $program, string $academicYear): Promotion
    {
        [$startYear, $endYear] = explode('-', $academicYear);

        $framework = (new Framework())
            ->setCode($program->value . '_' . $academicYear)
            ->setTitle($program->label())
            ->setSlug($program->value . '-' . $academicYear)
            ->setLevel(6)
            ->setStartAt(new \DateTimeImmutable($startYear . '-09-01'))
            ->setEndAt(new \DateTimeImmutable($endYear . '-08-31'));

        return (new Promotion())
            ->setProgram($program)
            ->setLabel(strtoupper($program->value) . ' ' . $academicYear)
            ->setSlug($program->value . '-' . $academicYear)
            ->setStartAt(new \DateTimeImmutable($startYear . '-09-01'))
            ->setEndAt(new \DateTimeImmutable($endYear . '-08-31'))
            ->setFramework($framework);
    }
}
