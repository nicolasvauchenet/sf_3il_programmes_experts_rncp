<?php

namespace App\Service\Import;

final readonly class ExerciseNormalizer
{
    private const ALLOWED_KEYS = [
        'title',
        'context',
        'objective',
        'objectif',
        'instructions',
        'deliverable',
        'livrable',
    ];

    /**
     * @return list<array{title: string, objective: string|null, instructions: string|null, deliverable: string|null}>|null
     */
    public function normalizeMany(mixed $value, string $source): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new \RuntimeException(sprintf('La collection d’exercices de "%s" doit être un tableau.', $source));
        }

        $exercises = [];

        foreach ($value as $index => $exercise) {
            $position = $index + 1;

            if (!is_array($exercise)) {
                throw new \RuntimeException(sprintf('L’exercice %d de "%s" doit être un objet.', $position, $source));
            }

            $unknownKeys = array_diff(array_keys($exercise), self::ALLOWED_KEYS);
            if ($unknownKeys !== []) {
                throw new \RuntimeException(sprintf(
                    'Clé(s) inconnue(s) dans l’exercice %d de "%s" : %s.',
                    $position,
                    $source,
                    implode(', ', $unknownKeys),
                ));
            }

            $title = $this->readRequiredString($exercise, 'title', $source, $position);
            $objective = $this->readAliasedString($exercise, 'objective', 'objectif', $source, $position);
            $instructions = $this->readAliasedString($exercise, 'instructions', 'context', $source, $position);
            $deliverable = $this->readAliasedString($exercise, 'deliverable', 'livrable', $source, $position);

            if ($objective !== null && $instructions !== null && $this->comparable($objective) === $this->comparable($instructions)) {
                $instructions = null;
            }

            $exercises[] = [
                'title' => $title,
                'objective' => $objective,
                'instructions' => $instructions,
                'deliverable' => $deliverable,
            ];
        }

        return $exercises;
    }

    /**
     * @param array<string, mixed> $exercise
     */
    private function readRequiredString(array $exercise, string $key, string $source, int $position): string
    {
        $value = $this->readString($exercise, $key, $source, $position);

        if ($value === null) {
            throw new \RuntimeException(sprintf('La clé "%s" est requise dans l’exercice %d de "%s".', $key, $position, $source));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $exercise
     */
    private function readAliasedString(
        array $exercise,
        string $canonicalKey,
        string $aliasKey,
        string $source,
        int $position,
    ): ?string {
        $canonicalValue = $this->readString($exercise, $canonicalKey, $source, $position);
        $aliasValue = $this->readString($exercise, $aliasKey, $source, $position);

        if ($canonicalValue !== null && $aliasValue !== null && $canonicalValue !== $aliasValue) {
            throw new \RuntimeException(sprintf(
                'Les clés "%s" et "%s" sont en conflit dans l’exercice %d de "%s".',
                $canonicalKey,
                $aliasKey,
                $position,
                $source,
            ));
        }

        return $canonicalValue ?? $aliasValue;
    }

    /**
     * @param array<string, mixed> $exercise
     */
    private function readString(array $exercise, string $key, string $source, int $position): ?string
    {
        if (!array_key_exists($key, $exercise) || $exercise[$key] === null) {
            return null;
        }

        if (!is_string($exercise[$key])) {
            throw new \RuntimeException(sprintf(
                'La clé "%s" de l’exercice %d de "%s" doit être une chaîne.',
                $key,
                $position,
                $source,
            ));
        }

        $value = trim($exercise[$key]);

        return $value !== '' ? $value : null;
    }

    private function comparable(string $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($value)));
    }
}
