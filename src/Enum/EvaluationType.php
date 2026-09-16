<?php

namespace App\Enum;

enum EvaluationType: string
{
    case ORAL = 'oral';
    case TECHNICAL = 'technical';
    case WRITING = 'writing';

    public function label(): string
    {
        return match ($this) {
            self::ORAL => 'Oral',
            self::TECHNICAL => 'Technique',
            self::WRITING => 'Rédaction',
        };
    }
}
