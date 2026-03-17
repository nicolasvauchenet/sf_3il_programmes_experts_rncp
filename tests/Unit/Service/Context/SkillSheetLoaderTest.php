<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Context;

use App\Dto\Context\SkillSheet;
use App\Service\Context\Loader\SkillSheetLoader;
use PHPUnit\Framework\TestCase;

final class SkillSheetLoaderTest extends TestCase
{
    private string $fixturesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesRoot = dirname(__DIR__, 3) . '/Fixtures/skill_sheet_loader';
    }

    public function testLoadReturnsSkillSheetForValidJson(): void
    {
        $loader = new SkillSheetLoader();
        $path = $this->fixturesRoot . '/valid_skill.json';

        $sheet = $loader->load('C1', $path);

        self::assertInstanceOf(SkillSheet::class, $sheet);

        self::assertSame('c1', $sheet->fileCode);
        self::assertSame($path, $sheet->path);

        self::assertSame('C1', $sheet->skillCode());
        self::assertSame('Concevoir une architecture logicielle', $sheet->title());
        self::assertSame('2025-2026', $sheet->academicYear());
        self::assertSame('BC01', $sheet->blocCode());
        self::assertSame('Conception', $sheet->blocName());

        self::assertSame(
            'Construire une solution cohérente et maintenable.',
            $sheet->description
        );

        self::assertSame(
            [
                'Identifier les besoins',
                'Définir les composants',
            ],
            $sheet->criteria
        );

        self::assertSame('RNCP39608', $sheet->meta['rncpCode']);

        self::assertIsArray($sheet->raw);
        self::assertArrayHasKey('meta', $sheet->raw);
        self::assertArrayHasKey('criteria', $sheet->raw);
    }

    public function testLoadThrowsExceptionWhenFileIsMissing(): void
    {
        $loader = new SkillSheetLoader();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fichier compétence introuvable ou illisible.');

        $loader->load('C1', $this->fixturesRoot . '/does_not_exist.json');
    }

    public function testLoadThrowsExceptionWhenJsonIsInvalid(): void
    {
        $loader = new SkillSheetLoader();
        $path = $this->fixturesRoot . '/invalid_json.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON compétence invalide.');

        $loader->load('C1', $path);
    }

    public function testLoadThrowsExceptionWhenMetaIsMissing(): void
    {
        $loader = new SkillSheetLoader();
        $path = $this->fixturesRoot . '/missing_meta.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Clé "meta" manquante ou invalide.');

        $loader->load('C1', $path);
    }

    public function testLoadThrowsExceptionWhenMetaTypeIsInvalid(): void
    {
        $loader = new SkillSheetLoader();
        $path = $this->fixturesRoot . '/invalid_type.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('meta.type invalide (attendu: "skill").');

        $loader->load('C1', $path);
    }

    public function testLoadThrowsExceptionWhenRequiredMetaBlocCodeIsMissing(): void
    {
        $loader = new SkillSheetLoader();
        $path = $this->fixturesRoot . '/missing_meta_bloc_code.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('meta.blocCode manquant.');

        $loader->load('C1', $path);
    }

    public function testLoadThrowsExceptionWhenRncpCodeIsNotAString(): void
    {
        $loader = new SkillSheetLoader();
        $path = $this->fixturesRoot . '/invalid_rncp_code_type.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('meta.rncpCode invalide (attendu: string).');

        $loader->load('C1', $path);
    }

    public function testLoadSanitizesEmptyDescriptionAndCriteria(): void
    {
        $loader = new SkillSheetLoader();
        $path = $this->fixturesRoot . '/empty_description_and_dirty_criteria.json';

        $sheet = $loader->load(' C2 ', $path);

        self::assertInstanceOf(SkillSheet::class, $sheet);

        self::assertSame('c2', $sheet->fileCode);
        self::assertSame('C2', $sheet->skillCode());
        self::assertSame('Implémenter une solution', $sheet->title());
        self::assertSame('2025-2026', $sheet->academicYear());
        self::assertSame('BC02', $sheet->blocCode());
        self::assertSame('Développement', $sheet->blocName());

        self::assertSame('', $sheet->description);

        self::assertSame(
            [
                'Écrire du code propre',
                'Tester le code',
            ],
            $sheet->criteria
        );

        self::assertArrayNotHasKey('rncpCode', $sheet->meta);
    }
}
