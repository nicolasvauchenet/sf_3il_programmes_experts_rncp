<?php

namespace App\Service\Context;

use App\Dto\Context\SkillSheet;

final class SkillSheetLoader
{
    public function load(string $fileCode, string $path): SkillSheet
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('Fichier compétence introuvable ou illisible.');
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            throw new \RuntimeException('Fichier compétence vide.');
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('JSON compétence invalide.', 0, $e);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('JSON compétence inattendu (pas un objet).');
        }

        $meta = $decoded['meta'] ?? null;
        if (!is_array($meta)) {
            throw new \RuntimeException('Clé "meta" manquante ou invalide.');
        }

        $type = $meta['type'] ?? null;
        if (!is_string($type) || strtolower($type) !== 'skill') {
            throw new \RuntimeException('meta.type invalide (attendu: "skill").');
        }

        foreach (['code', 'title', 'academicYear', 'blocCode', 'blocName'] as $k) {
            $v = $meta[$k] ?? null;
            if (!is_string($v) || $v === '') {
                throw new \RuntimeException(sprintf('meta.%s manquant.', $k));
            }
        }

        foreach (['rncpCode'] as $k) {
            $v = $meta[$k] ?? null;
            if ($v !== null && !is_string($v)) {
                throw new \RuntimeException(sprintf('meta.%s invalide (attendu: string).', $k));
            }
        }

        $description = $decoded['description'] ?? null;
        if (!is_string($description) || trim($description) === '') {
            $description = '';
        }

        $criteria = $this->sanitizeStringList($decoded['criteria'] ?? null);

        return new SkillSheet(
            fileCode: strtolower(trim($fileCode)),
            path: $path,
            meta: $meta,
            description: trim($description),
            criteria: $criteria,
            raw: $decoded,
        );
    }

    /**
     * @param mixed $list
     * @return array<int,string>
     */
    private function sanitizeStringList(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $out = [];

        foreach ($list as $item) {
            if (!is_string($item)) {
                continue;
            }

            $item = trim($item);
            if ($item === '') {
                continue;
            }

            $out[] = $item;
        }

        return $out;
    }
}
