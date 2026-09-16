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

    public function normalizeFrameworkCode(string $frameworkCode): string
    {
        $frameworkCode = strtoupper(trim($frameworkCode));

        if ($frameworkCode === '') {
            return '';
        }

        if (!str_starts_with($frameworkCode, 'RNCP')) {
            return 'RNCP' . $frameworkCode;
        }

        return $frameworkCode;
    }

    public function normalizeBlockCode(string $blockCode): string
    {
        return strtoupper(trim($blockCode));
    }

    public function normalizeShortSkillCode(string $skillCode): string
    {
        return strtoupper(trim($skillCode));
    }

    public function normalizeSkillCode(string $frameworkCode, string $blockCode, string $skillCode): string
    {
        $frameworkCode = $this->normalizeFrameworkCode($frameworkCode);
        $blockCode = $this->normalizeBlockCode($blockCode);
        $skillCode = $this->normalizeShortSkillCode($skillCode);

        if ($frameworkCode === '' || $blockCode === '' || $skillCode === '') {
            return '';
        }

        if (preg_match('/^RNCP\d+-BC\d{2}-C\d{2}$/i', $skillCode) === 1) {
            return strtoupper($skillCode);
        }

        if (preg_match('/^BC\d{2}-C\d{2}$/i', $skillCode) === 1) {
            return sprintf('%s-%s', $frameworkCode, strtoupper($skillCode));
        }

        return sprintf('%s-%s-%s', $frameworkCode, $blockCode, $skillCode);
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
