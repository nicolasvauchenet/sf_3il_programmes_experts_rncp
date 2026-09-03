<?php

namespace App\Service\Context;

final readonly class ModuleTextBlockFormatter
{
    /**
     * @return array{description?: string, intro?: string, items?: list<string>}
     */
    public function formatObjectives(?string $value): array
    {
        $block = $this->normalize($value);
        $description = $block['description'] ?? null;

        if ($description === null || !preg_match('/^(?<intro>.+?:)\s+(?<content>.+)$/u', $description, $matches)) {
            return $block;
        }

        $items = preg_split('/(?<=\))\s+(?=\p{Lu})/u', trim($matches['content'])) ?: [];
        $items = array_values(array_filter(array_map('trim', $items)));

        if (count($items) < 2 || !$this->allItemsEndWithLevel($items)) {
            return $block;
        }

        return [
            ...$block,
            'intro' => trim($matches['intro']),
            'items' => $items,
        ];
    }

    /**
     * @return array{description?: string, paragraphs?: list<string>}
     */
    public function formatPrerequisites(?string $value): array
    {
        $block = $this->normalize($value);
        $description = $block['description'] ?? null;

        if ($description === null) {
            return $block;
        }

        $paragraphs = preg_split('/(?<=[.!?])\s+(?=\p{Lu})/u', $description) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

        return [
            ...$block,
            'paragraphs' => $paragraphs,
        ];
    }

    /** @return array{description?: string} */
    private function normalize(?string $value): array
    {
        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $description = trim((string)($decoded['description'] ?? $decoded['text'] ?? $decoded['summary'] ?? ''));

            return $description !== '' ? [...$decoded, 'description' => $description] : $decoded;
        }

        return ['description' => $value];
    }

    /** @param list<string> $items */
    private function allItemsEndWithLevel(array $items): bool
    {
        foreach ($items as $item) {
            if (preg_match('/\([\p{L}\s-]+\)$/u', $item) !== 1) {
                return false;
            }
        }

        return true;
    }
}
