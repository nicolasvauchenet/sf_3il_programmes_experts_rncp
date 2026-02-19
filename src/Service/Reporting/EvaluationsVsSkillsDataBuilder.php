<?php

namespace App\Service\Reporting;

final class EvaluationsVsSkillsDataBuilder
{
    public function buildEpreuvesMapping(array $json): array
    {
        /** @var array<string, array<int, string>> $competenceToEpreuves */
        $competenceToEpreuves = $json['mappings']['competence_to_epreuves'] ?? [];

        $epreuvesMapping = [];

        foreach ($competenceToEpreuves as $competenceCode => $epreuvesCodes) {
            foreach ($epreuvesCodes as $ecCode) {
                $epreuvesMapping[$ecCode] ??= [];
                $epreuvesMapping[$ecCode][] = (string)$competenceCode;
            }
        }

        foreach ($epreuvesMapping as $ecCode => $competences) {
            $competences = array_values(array_unique($competences));
            sort($competences);
            $epreuvesMapping[$ecCode] = $competences;
        }

        ksort($epreuvesMapping);

        return $epreuvesMapping;
    }

    public function buildChartSeries(array $json): array
    {
        $epreuvesMapping = $this->buildEpreuvesMapping($json);

        $labels = [];
        $values = [];

        foreach ($epreuvesMapping as $ec => $competences) {
            $labels[] = (string)$ec;
            $values[] = count($competences);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'skillsByEvaluation' => $epreuvesMapping,
        ];
    }
}
