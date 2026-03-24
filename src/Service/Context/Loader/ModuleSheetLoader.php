<?php

namespace App\Service\Context\Loader;

use App\Dto\Context\ModuleSheet;

final class ModuleSheetLoader
{
    public function load(string $fileCode, string $path): ModuleSheet
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('Fichier module introuvable ou illisible.');
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            throw new \RuntimeException('Fichier module vide.');
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('JSON module invalide.', 0, $e);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('JSON module inattendu (pas un objet).');
        }

        $meta = $decoded['meta'] ?? null;
        if (!is_array($meta)) {
            throw new \RuntimeException('Clé "meta" manquante ou invalide.');
        }

        $type = $meta['type'] ?? null;
        if (!is_string($type) || strtolower($type) !== 'module') {
            throw new \RuntimeException('meta.type invalide (attendu: "module").');
        }

        foreach (['code', 'title', 'academicYear'] as $k) {
            $v = $meta[$k] ?? null;
            if (!is_string($v) || $v === '') {
                throw new \RuntimeException(sprintf('meta.%s manquant.', $k));
            }
        }

        $skills = is_array($decoded['skills'] ?? null) ? $decoded['skills'] : [];
        $objectives = is_array($decoded['objectives'] ?? null) ? $decoded['objectives'] : [];
        $prerequisites = is_array($decoded['prerequisites'] ?? null) ? $decoded['prerequisites'] : [];
        $outline = is_array($decoded['outline'] ?? null) ? $decoded['outline'] : [];
        $exercises = is_array($decoded['exercises'] ?? null) ? $decoded['exercises'] : [];
        $bibliography = is_array($decoded['bibliography'] ?? null) ? $decoded['bibliography'] : [];
        $teachingMethods = is_array($decoded['teachingMethods'] ?? null) ? $decoded['teachingMethods'] : [];

        $skills = $this->sanitizeSkills($skills);
        $teachingMethods = $this->sanitizeStringList($teachingMethods);

        return new ModuleSheet(
            fileCode: strtolower(trim($fileCode)),
            path: $path,
            meta: $meta,
            skills: $skills,
            skillsWithCriteria: [],
            evaluations: [],
            objectives: $objectives,
            prerequisites: $prerequisites,
            outline: $outline,
            exercises: $exercises,
            bibliography: $bibliography,
            teachingMethods: $teachingMethods,
            raw: $decoded,
        );
    }

    /**
     * @param array<int,mixed> $skills
     * @return array<int,array{code:string,description:string}>
     */
    private function sanitizeSkills(array $skills): array
    {
        $out = [];

        foreach ($skills as $s) {
            if (!is_array($s)) {
                continue;
            }

            $code = $s['code'] ?? null;
            $desc = $s['description'] ?? null;

            if (!is_string($code) || $code === '') {
                continue;
            }

            if (!is_string($desc) || $desc === '') {
                continue;
            }

            $out[] = [
                'code' => trim($code),
                'description' => trim($desc),
            ];
        }

        return $out;
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
            if (!is_string($item) || $item === '') {
                continue;
            }

            $out[] = trim($item);
        }

        return $out;
    }
}
