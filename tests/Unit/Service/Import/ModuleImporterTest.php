<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Dto\Import\ImportReport;
use App\Entity\Block;
use App\Entity\Framework;
use App\Entity\Module;
use App\Entity\Promotion;
use App\Entity\Skill;
use App\Service\Import\CodeNormalizer;
use App\Service\Import\ExerciseNormalizer;
use App\Service\Import\ModuleImporter;
use App\Service\Import\RelationSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModuleImporterTest extends TestCase
{
    #[DataProvider('skillSources')]
    public function testImportsAuthoritativeSkills(?array $detail, array $expectedCodes, bool $existing): void
    {
        $code = 'RNCP39608-BC03-FM03';
        $framework = (new Framework())->setCode('RNCP39608');
        $promotion = (new Promotion())->setFramework($framework);
        $block = (new Block())->setCode('BC03');
        $skills = [];
        $shortCodes = [];
        $module = (new Module())->setCode($code)->setPromotion($promotion);
        foreach (range(18, 25) as $number) {
            $shortCode = 'C' . $number;
            $fullCode = 'RNCP39608-BC03-' . $shortCode;
            $skills[$fullCode] = (new Skill())->setCode($fullCode);
            $shortCodes[] = $shortCode;
            if ($existing) {
                $module->addSkill($skills[$fullCode]);
            }
        }

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->with(['promotion' => $promotion, 'code' => $code])
            ->willReturn($existing ? $module : null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(Module::class)->willReturn($repository);
        $importer = new ModuleImporter($entityManager, new RelationSyncService(), new CodeNormalizer(), new ExerciseNormalizer());
        $structure = ['modules' => [['code' => $code, 'blockCode' => 'BC03', 'skills' => $shortCodes]]];
        $details = $detail === null ? [] : [$code => $detail];

        // Repeating the import must not accumulate skill relations.
        for ($run = 0; $run < 2; ++$run) {
            $result = $importer->import($structure, $details, $promotion, ['BC03' => $block], $skills, [], new ImportReport());
            $expectedSkills = array_map(static fn(string $shortCode): Skill => $skills['RNCP39608-BC03-' . $shortCode], $expectedCodes);
            self::assertSame($expectedSkills, array_values($result[$code]->getSkills()->toArray()));
        }
    }

    public static function skillSources(): iterable
    {
        yield 'new module uses the two detail skills' => [['skills' => [['code' => 'C21'], ['code' => 'C22']]], ['C21', 'C22'], false];
        yield 'reimport removes the six obsolete relations' => [['skills' => [['code' => 'C21'], ['code' => 'C22']]], ['C21', 'C22'], true];
        yield 'explicit empty list clears relations' => [['skills' => []], [], true];
        yield 'missing field keeps structure compatibility' => [[], ['C18', 'C19', 'C20', 'C21', 'C22', 'C23', 'C24', 'C25'], true];
        yield 'missing detail keeps structure compatibility' => [null, ['C18', 'C19', 'C20', 'C21', 'C22', 'C23', 'C24', 'C25'], false];
        yield 'string codes are normalized and deduplicated' => [['skills' => [' c21 ', 'RNCP39608-BC03-C22', 'C21']], ['C21', 'C22'], true];
    }

    #[DataProvider('invalidSkills')]
    public function testRejectsMalformedDetailSkills(mixed $skills): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->testImportsAuthoritativeSkills(['skills' => $skills], [], true);
    }

    public static function invalidSkills(): iterable
    {
        yield 'null list' => [null];
        yield 'string list' => ['C21'];
        yield 'missing code' => [[['description' => 'Missing code']]];
        yield 'empty code' => [[['code' => ' ']]];
    }
}
