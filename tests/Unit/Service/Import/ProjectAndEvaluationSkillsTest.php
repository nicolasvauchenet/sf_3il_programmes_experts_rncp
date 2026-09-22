<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Dto\Import\ImportReport;
use App\Entity\Block;
use App\Entity\Evaluation;
use App\Entity\Framework;
use App\Entity\Project;
use App\Entity\Promotion;
use App\Entity\Skill;
use App\Service\Import\CodeNormalizer;
use App\Service\Import\EnumResolver;
use App\Service\Import\EvaluationImporter;
use App\Service\Import\ProjectImporter;
use App\Service\Import\RelationSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectAndEvaluationSkillsTest extends TestCase
{
    #[DataProvider('skillSources')]
    public function testDetailSkillsTakePrecedence(string $kind, ?array $detail, array $expectedCodes, bool $existing): void
    {
        $framework = (new Framework())->setCode('RNCP39608');
        $promotion = (new Promotion())->setFramework($framework);
        $block = (new Block())->setCode('BC03');
        $code = 'RNCP39608-BC03-' . ($kind === 'projects' ? 'PR03' : 'EC06');
        $entity = $kind === 'projects'
            ? (new Project())->setPromotion($promotion)
            : (new Evaluation())->setFramework($framework);
        $entity->setCode($code)->setBlock($block);
        $skills = [];
        foreach (['C21', 'C22', 'C23'] as $shortCode) {
            $fullCode = 'RNCP39608-BC03-' . $shortCode;
            $skills[$fullCode] = (new Skill())->setCode($fullCode);
            if ($existing) {
                $entity->addSkill($skills[$fullCode]);
            }
        }
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($existing ? $entity : null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with($entity::class)->willReturn($repository);
        $structure = [$kind => [['code' => $code, 'blockCode' => 'BC03', 'skills' => ['C21', 'C22', 'C23']]]];
        $files = $detail === null ? [] : [$code => $detail];

        for ($run = 0; $run < 2; ++$run) {
            if ($kind === 'projects') {
                $importer = new ProjectImporter($entityManager, new RelationSyncService(), new CodeNormalizer());
                $result = $importer->import($structure, $files, $promotion, ['BC03' => $block], [], $skills, [], new ImportReport());
            } else {
                $importer = new EvaluationImporter($entityManager, new RelationSyncService(), new EnumResolver(), new CodeNormalizer());
                $result = $importer->import($structure, $files, $framework, ['BC03' => $block], [], [], $skills, new ImportReport());
                // The full framework import synchronizes evaluations a second time.
                $importer->syncRelations($structure, $files, $result, [], [], $skills);
            }
            $expectedSkills = array_map(static fn(string $shortCode): Skill => $skills['RNCP39608-BC03-' . $shortCode], $expectedCodes);
            self::assertSame($expectedSkills, array_values($result[$code]->getSkills()->toArray()));
        }
    }

    public static function skillSources(): iterable
    {
        foreach (['projects', 'evaluations'] as $kind) {
            yield "$kind creation" => [$kind, ['skills' => [['code' => 'C21']]], ['C21'], false];
            yield "$kind reimport" => [$kind, ['skills' => [['code' => 'C21']]], ['C21'], true];
            yield "$kind explicit empty list" => [$kind, ['skills' => []], [], true];
            yield "$kind missing field" => [$kind, [], ['C21', 'C22', 'C23'], true];
            yield "$kind normalized string codes" => [$kind, ['skills' => [' c21 ', 'RNCP39608-BC03-C22', 'C21']], ['C21', 'C22'], true];
        }
        yield 'evaluation missing detail' => ['evaluations', null, ['C21', 'C22', 'C23'], true];
    }
}
