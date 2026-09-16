<?php

namespace App\Enum;

enum ImportStrategy: string
{
    case CREATE_FULL = 'create_full';
    case UPDATE_FULL = 'update_full';
    case CREATE_PROMOTION = 'create_promotion';
    case UPDATE_PROMOTION = 'update_promotion';

    public function label(): string
    {
        return match ($this) {
            self::CREATE_FULL => 'Création complète (framework + promotion)',
            self::UPDATE_FULL => 'Remplacement complet (framework + promotion)',
            self::CREATE_PROMOTION => 'Création de promotion',
            self::UPDATE_PROMOTION => 'Remplacement de promotion',
        };
    }
}
