<?php

namespace App\Service\Import;

final readonly class DetailSkillCodesResolver
{
    /**
     * @param array<string, mixed>|null $detail
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    public function resolve(?array $detail, array $row): array
    {
        if ($detail === null || !array_key_exists('skills', $detail)) {
            return (array)($row['skills'] ?? []);
        }

        // An explicit empty list also takes precedence over structure.json.
        if (!is_array($detail['skills'])) {
            throw new \InvalidArgumentException(sprintf('Record %s: skills must be an array.', $row['code'] ?? ''));
        }

        $codes = [];
        foreach ($detail['skills'] as $skill) {
            $code = is_array($skill) ? ($skill['code'] ?? null) : $skill;
            if (!is_string($code) || trim($code) === '') {
                throw new \InvalidArgumentException(sprintf('Record %s: each skill must have a non-empty code.', $row['code'] ?? ''));
            }
            $codes[] = trim($code);
        }

        return array_values(array_unique($codes));
    }
}
