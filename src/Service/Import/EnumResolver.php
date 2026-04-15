<?php

namespace App\Service\Import;

use App\Enum\EvaluationType;
use App\Enum\Program;

final class EnumResolver
{
    public function resolveProgram(string $code): Program
    {
        return match (mb_strtolower(trim($code))) {
            'asrc' => Program::ASRC,
            'cdwfs' => Program::CDWFS,
            'eadl' => Program::EADL,
            'eris' => Program::ERIS,
            default => throw new \InvalidArgumentException(sprintf('Programme inconnu : "%s".', $code)),
        };
    }

    public function resolveEvaluationType(string $label): EvaluationType
    {
        $normalized = mb_strtolower(trim($label));

        return match (true) {
            str_contains($normalized, 'oral'),
            str_contains($normalized, 'soutenance') => EvaluationType::ORAL,

            str_contains($normalized, 'écrit'),
            str_contains($normalized, 'ecrit'),
            str_contains($normalized, 'rédaction'),
            str_contains($normalized, 'redaction'),
            str_contains($normalized, 'rapport'),
            str_contains($normalized, 'dossier'),
            str_contains($normalized, 'compte rendu') => EvaluationType::WRITING,

            default => EvaluationType::TECHNICAL,
        };
    }
}
