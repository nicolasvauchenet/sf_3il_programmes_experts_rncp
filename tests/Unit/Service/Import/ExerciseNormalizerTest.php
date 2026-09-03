<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Service\Import\ExerciseNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExerciseNormalizerTest extends TestCase
{
    private ExerciseNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ExerciseNormalizer();
    }

    public function testNormalizesLegacyExercise(): void
    {
        self::assertSame([
            [
                'title' => 'Modéliser un système',
                'objective' => 'Appliquer UML',
                'instructions' => 'Concevoir les diagrammes',
                'deliverable' => null,
            ],
        ], $this->normalizer->normalizeMany([
            [
                'title' => ' Modéliser un système ',
                'context' => 'Concevoir les diagrammes',
                'objective' => 'Appliquer UML',
            ],
        ], 'FM01'));
    }

    public function testNormalizesNewExercise(): void
    {
        self::assertSame([
            [
                'title' => 'Modéliser un système',
                'objective' => 'Appliquer UML',
                'instructions' => 'Concevoir les diagrammes',
                'deliverable' => 'Les diagrammes UML',
            ],
        ], $this->normalizer->normalizeMany([
            [
                'title' => 'Modéliser un système',
                'objectif' => 'Appliquer UML',
                'instructions' => 'Concevoir les diagrammes',
                'livrable' => 'Les diagrammes UML',
            ],
        ], 'FM01'));
    }

    public function testRemovesDuplicatedLegacyContext(): void
    {
        $exercises = $this->normalizer->normalizeMany([
            [
                'title' => 'UML',
                'context' => "  Appliquer   UML\ncorrectement ",
                'objective' => 'appliquer uml correctement',
            ],
        ], 'FM01');

        self::assertNull($exercises[0]['instructions']);
    }

    public function testAcceptsIdenticalAliases(): void
    {
        $exercises = $this->normalizer->normalizeMany([
            [
                'title' => 'UML',
                'objective' => 'Appliquer UML',
                'objectif' => 'Appliquer UML',
            ],
        ], 'FM01');

        self::assertSame('Appliquer UML', $exercises[0]['objective']);
    }

    #[DataProvider('invalidExerciseProvider')]
    public function testRejectsInvalidExercises(mixed $value, string $message): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->normalizer->normalizeMany($value, 'FM01');
    }

    public static function invalidExerciseProvider(): iterable
    {
        yield 'invalid collection' => ['broken', 'doit être un tableau'];
        yield 'invalid exercise' => [[false], 'doit être un objet'];
        yield 'missing title' => [[['objectif' => 'Tester']], 'La clé "title" est requise'];
        yield 'invalid value' => [[['title' => 'Tester', 'objectif' => []]], 'doit être une chaîne'];
        yield 'unknown key' => [[['title' => 'Tester', 'resultat' => 'OK']], 'Clé(s) inconnue(s)'];
        yield 'conflicting aliases' => [[[
            'title' => 'Tester',
            'objective' => 'Valeur A',
            'objectif' => 'Valeur B',
        ]], 'sont en conflit'];
    }
}
