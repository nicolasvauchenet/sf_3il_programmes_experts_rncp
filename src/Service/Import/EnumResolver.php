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

    public function resolveEvaluationType(string $label, ?string $title = null): EvaluationType
    {
        $normalized = mb_strtolower(trim($label));
        $normalizedTitle = mb_strtolower(trim((string)$title));

        return match (true) {
            str_contains($normalized, 'oral'),
            str_contains($normalized, 'soutenance'),
            str_contains($normalizedTitle, 'oral'),
            str_contains($normalizedTitle, 'soutenance'),
            str_contains($normalizedTitle, 'présentation'),
            str_contains($normalizedTitle, 'presentation') => EvaluationType::ORAL,

            str_contains($normalizedTitle, 'mise en place'),
            str_contains($normalizedTitle, 'mise en service'),
            str_contains($normalizedTitle, 'installation'),
            str_contains($normalizedTitle, 'déploiement'),
            str_contains($normalizedTitle, 'deploiement'),
            str_contains($normalizedTitle, 'configuration'),
            str_contains($normalizedTitle, 'administration'),
            str_contains($normalizedTitle, 'virtualisation') => EvaluationType::TECHNICAL,

            str_contains($normalized, 'écrit'),
            str_contains($normalized, 'ecrit'),
            str_contains($normalized, 'rédaction'),
            str_contains($normalized, 'redaction'),
            str_contains($normalized, 'rapport'),
            str_contains($normalized, 'dossier'),
            str_contains($normalized, 'compte rendu'),
            str_contains($normalizedTitle, 'rédaction'),
            str_contains($normalizedTitle, 'redaction'),
            str_contains($normalizedTitle, 'rapport'),
            str_contains($normalizedTitle, 'dossier'),
            str_contains($normalizedTitle, 'compte rendu'),
            str_contains($normalizedTitle, 'analyse'),
            str_contains($normalizedTitle, 'préconisation'),
            str_contains($normalizedTitle, 'preconisation') => EvaluationType::WRITING,

            default => EvaluationType::TECHNICAL,
        };
    }
}
