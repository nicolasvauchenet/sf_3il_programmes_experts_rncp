<?php

namespace App\Service\Context;

use App\Dto\Context\EvaluationSheet;

final class EvaluationSheetLoader
{
    public function load(string $fileCode, string $path): EvaluationSheet
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('Fichier évaluation introuvable ou illisible.');
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            throw new \RuntimeException('Fichier évaluation vide.');
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('JSON évaluation invalide.', 0, $e);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('JSON évaluation inattendu (pas un objet).');
        }

        $meta = $decoded['meta'] ?? null;
        if (!is_array($meta)) {
            throw new \RuntimeException('Clé "meta" manquante ou invalide.');
        }

        $type = $meta['type'] ?? null;
        if (!is_string($type) || strtolower($type) !== 'evaluation') {
            throw new \RuntimeException('meta.type invalide (attendu: "evaluation").');
        }

        foreach (['code', 'title', 'academicYear'] as $k) {
            $v = $meta[$k] ?? null;
            if (!is_string($v) || trim($v) === '') {
                throw new \RuntimeException(sprintf('meta.%s manquant.', $k));
            }
        }

        $skills = $this->sanitizeSimpleEntries($decoded['skills'] ?? []);
        $criteria = $this->sanitizeCriteria($decoded['criteria'] ?? []);
        $modalities = is_array($decoded['modalities'] ?? null) ? $decoded['modalities'] : [];
        $exam = is_array($decoded['exam'] ?? null) ? $decoded['exam'] : [];

        return new EvaluationSheet(
            fileCode: strtolower(trim($fileCode)),
            path: $path,
            meta: $meta,
            evaluationNumber: (int)($decoded['evaluationNumber'] ?? 0),
            description: is_string($decoded['description'] ?? null) ? trim((string)$decoded['description']) : '',
            skills: $skills,
            criteria: $criteria,
            modalities: $modalities,
            exam: $exam,
            raw: $decoded,
        );
    }

    /**
     * @param mixed $entries
     * @return array<int,array{code:string,description:string}>
     */
    private function sanitizeSimpleEntries(mixed $entries): array
    {
        if (!is_array($entries)) {
            return [];
        }

        $out = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $code = $entry['code'] ?? null;
            $description = $entry['description'] ?? null;

            if (!is_string($code) || trim($code) === '') {
                continue;
            }

            if (!is_string($description) || trim($description) === '') {
                continue;
            }

            $out[] = [
                'code' => trim($code),
                'description' => trim($description),
            ];
        }

        return $out;
    }

    /**
     * @param mixed $entries
     * @return array<int,array{
     *     code:string,
     *     title:string,
     *     description:string,
     *     indicators:array<int,string>
     * }>
     */
    private function sanitizeCriteria(mixed $entries): array
    {
        if (!is_array($entries)) {
            return [];
        }

        $out = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $code = $entry['code'] ?? null;
            if (!is_string($code) || trim($code) === '') {
                continue;
            }

            $title = $entry['title'] ?? '';
            $description = $entry['description'] ?? '';
            $indicators = $entry['indicators'] ?? [];

            $cleanIndicators = [];
            if (is_array($indicators)) {
                foreach ($indicators as $indicator) {
                    if (is_string($indicator) && trim($indicator) !== '') {
                        $cleanIndicators[] = trim($indicator);
                    }
                }
            }

            $out[] = [
                'code' => trim($code),
                'title' => is_string($title) ? trim($title) : '',
                'description' => is_string($description) ? trim($description) : '',
                'indicators' => $cleanIndicators,
            ];
        }

        return $out;
    }
}
