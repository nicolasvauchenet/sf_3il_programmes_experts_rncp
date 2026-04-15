<?php

namespace App\Enum;

enum Program: string
{
    case ASRC = 'asrc';
    case CDWFS = 'cdwfs';
    case EADL = 'eadl';
    case ERIS = 'eris';

    public function label(): string
    {
        return match ($this) {
            self::ASRC => 'Administrateur Système, Réseaux et Cybersécurité',
            self::CDWFS => 'Concepteur Développeur Web Full Stack',
            self::EADL => 'Expert en Architecture et Développement Logiciel',
            self::ERIS => 'Expert Réseaux, Infrastructures et Sécurité',
        };
    }
}
