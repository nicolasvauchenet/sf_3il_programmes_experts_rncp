<?php

declare(strict_types=1);

namespace App\Service\Framework;

final readonly class JsonFileLoader
{
    public function readFile(string $path): string
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            return '';
        }

        return $content;
    }

    /**
     * @return array{ok: true, data: mixed}|array{ok: false, error: string}
     */
    public function decode(string $json): array
    {
        if (trim($json) === '') {
            return ['ok' => false, 'error' => 'Empty JSON content.'];
        }

        try {
            $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return ['ok' => true, 'data' => $data];
    }
}
