<?php

declare(strict_types=1);

namespace App\Warning;

final class WarningCollector
{
    /** @var list<Warning> */
    private array $warnings = [];

    public function add(Warning $warning): void
    {
        $this->warnings[] = $warning;
    }

    /** @return list<Warning> */
    public function all(): array
    {
        return $this->warnings;
    }

    public function hasAny(): bool
    {
        return $this->warnings !== [];
    }

    public function clear(): void
    {
        $this->warnings = [];
    }
}
