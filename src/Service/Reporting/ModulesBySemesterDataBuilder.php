<?php

namespace App\Service\Reporting;

final class ModulesBySemesterDataBuilder
{
    public function buildChartSeries(array $json): array
    {
        $annees = $json['annees'] ?? $json['years'] ?? [];

        $labels = [];
        $values = [];

        foreach ($annees as $anneeIndex => $annee) {
            $anneeCode = $annee['code'] ?? $annee['id'] ?? $annee['label'] ?? ('AS'.($anneeIndex + 1));
            $semestres = $annee['semestres'] ?? $annee['semesters'] ?? [];

            foreach ($semestres as $semIndex => $semestre) {
                $semCode = $semestre['code'] ?? $semestre['id'] ?? $semestre['label'] ?? ('S'.($semIndex + 1));
                $modules = $semestre['modules'] ?? [];

                $labels[] = sprintf('%s %s', (string)$anneeCode, (string)$semCode);
                $values[] = is_array($modules) ? count($modules) : 0;
            }
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
