<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Entity\Block;
use App\Entity\Framework;
use App\Entity\Module;
use App\Entity\Promotion;
use App\Enum\Program;
use App\Service\Import\DatabaseDatasetInheritanceProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class DatabaseDatasetInheritanceProviderTest extends TestCase
{
    public function testBuildsNextYearDatasetFromLatestDatabasePromotion(): void
    {
        $framework = (new Framework())
            ->setCode('RNCP39765')
            ->setTitle('Expert en Architecture et Développement Logiciel')
            ->setLevel(7)
            ->setStartAt(new \DateTimeImmutable('2025-09-01'))
            ->setEndAt(new \DateTimeImmutable('2026-08-31 23:59:59'));

        $block = (new Block())
            ->setCode('BC01')
            ->setTitle('Architecture')
            ->setPosition(1);
        $framework->addBlock($block);

        $promotion = (new Promotion())
            ->setProgram(Program::EADL)
            ->setLabel('EADL 2025-2026')
            ->setStartAt(new \DateTimeImmutable('2025-09-01'))
            ->setEndAt(new \DateTimeImmutable('2026-08-31 23:59:59'))
            ->setFramework($framework);

        $module = (new Module())
            ->setCode('RNCP39765-BC01-FM01')
            ->setTitle('Architecture logicielle')
            ->setPosition(1)
            ->setBlock($block)
            ->setDurationDays(5)
            ->setDurationHours(35)
            ->setExercises([[
                'title' => 'Concevoir',
                'objective' => 'Concevoir une architecture',
                'instructions' => null,
                'deliverable' => 'Un diagramme',
            ]]);
        $promotion->addModule($module);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['program' => Program::EADL], ['startAt' => 'DESC'])
            ->willReturn([$promotion]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($repository);

        $dataset = (new DatabaseDatasetInheritanceProvider($entityManager))->inherit('eadl_2026-2027');

        self::assertSame('eadl_2026-2027', $dataset['structure']['meta']['datasetCode']);
        self::assertSame('2026-2027', $dataset['structure']['meta']['academicYear']);
        self::assertSame('39765', $dataset['structure']['meta']['rncpCode']);
        self::assertSame('RNCP39765-BC01-FM01', $dataset['structure']['modules'][0]['code']);
        self::assertSame(
            'Un diagramme',
            $dataset['modules']['RNCP39765-BC01-FM01']['exercises'][0]['deliverable'],
        );
    }

    public function testFailsWhenNoPreviousPromotionExists(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Aucun référentiel antérieur');

        (new DatabaseDatasetInheritanceProvider($entityManager))->inherit('eadl_2026-2027');
    }
}
