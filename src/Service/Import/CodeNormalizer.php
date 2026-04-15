<?php

namespace App\Service\Import;

final class CodeNormalizer
{
    public function normalize(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        return $this->normalizeProjectCode($code);
    }

    public function normalizeProjectCode(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        return preg_replace('/-PRJ(\d+)$/', '-PR$1', $code) ?? $code;
    }

    /**
     * @param array<int, string> $codes
     * @return array<int, string>
     */
    public function normalizeMany(array $codes): array
    {
        $normalized = [];

        foreach ($codes as $code) {
            $value = $this->normalize((string)$code);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }
}
