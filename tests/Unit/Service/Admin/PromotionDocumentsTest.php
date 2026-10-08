<?php

declare(strict_types=1);
namespace App\Tests\Unit\Service\Admin;

use App\Command\ImportSourceDocumentsCommand;
use App\Entity\{Promotion, PromotionDocument};
use App\Enum\Program;
use App\Service\Admin\PromotionDocumentStorage;
use Doctrine\ORM\{EntityManagerInterface, EntityRepository};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class PromotionDocumentsTest extends TestCase
{
    public function testLegacyImportCopiesFilesOnceWithoutChangingSources(): void
    {
        $root = sys_get_temp_dir().'/source-documents-'.bin2hex(random_bytes(6));
        $filesystem = new Filesystem();
        $filesystem->mkdir($root.'/source/eadl_2026-2027');
        $source = $root.'/source/eadl_2026-2027/referentiel-rncp.pdf';
        file_put_contents($source, '%PDF-1.4 test');
        try {
            $promotion = (new Promotion())->setProgram(Program::EADL)->setStartAt(new \DateTimeImmutable('2026-09-01'))->setEndAt(new \DateTimeImmutable('2027-08-31'));
            $promotions = $this->createMock(EntityRepository::class);
            $promotions->method('findAll')->willReturn([$promotion]);
            $documents = $this->createMock(EntityRepository::class);
            $documents->method('count')->willReturnOnConsecutiveCalls(0, 1);
            $em = $this->createMock(EntityManagerInterface::class);
            $em->method('getRepository')->willReturnMap([[Promotion::class, $promotions], [PromotionDocument::class, $documents]]);
            $storedDocument = null;
            $em->expects(self::once())->method('persist')->willReturnCallback(static function ($document) use (&$storedDocument): void { $storedDocument = $document; });
            $storage = new PromotionDocumentStorage($root.'/documents');
            $command = new CommandTester(new ImportSourceDocumentsCommand($em, $storage, $root.'/source'));
            self::assertSame(0, $command->execute([]));
            self::assertSame(0, $command->execute([]));
            self::assertSame($promotion, $storedDocument->promotion);
            self::assertSame('Référentiel RNCP', $storedDocument->label);
            self::assertSame(file_get_contents($source), file_get_contents($storage->path($storedDocument->filename)));
            self::assertCount(1, glob($root.'/documents/*'));
            $storage->remove($storedDocument->filename);
            self::assertFileDoesNotExist($storage->path($storedDocument->filename));
            self::assertFileExists($source);
        } finally { $filesystem->remove($root); }
    }

    public function testStorageRejectsTraversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new PromotionDocumentStorage(sys_get_temp_dir()))->path('../secret.pdf');
    }
}
