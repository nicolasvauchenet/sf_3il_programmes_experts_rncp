<?php

namespace App\Enum;

enum ImportMode: string
{
    case FULL = 'full';
    case MODULES_PROJECTS = 'modules_projects';

    public function label(): string
    {
        return match ($this) {
            self::FULL => 'Import complet',
            self::MODULES_PROJECTS => 'Import matières et projets',
        };
    }
}
