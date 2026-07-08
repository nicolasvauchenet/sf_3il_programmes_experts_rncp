<?php

namespace App\Dto\Import;

final class ImportReport
{
    /**
     * @var array<string, array{created:int, updated:int, deleted:int}>
     */
    private array $stats = [];

    public function markCreated(string $section): void
    {
        $this->initSection($section);
        $this->stats[$section]['created']++;
    }

    public function markUpdated(string $section): void
    {
        $this->initSection($section);
        $this->stats[$section]['updated']++;
    }

    public function markDeleted(string $section): void
    {
        $this->initSection($section);
        $this->stats[$section]['deleted']++;
    }

    /**
     * @return array<string, array{created:int, updated:int, deleted:int}>
     */
    public function all(): array
    {
        return $this->stats;
    }

    private function initSection(string $section): void
    {
        if (!isset($this->stats[$section])) {
            $this->stats[$section] = [
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
            ];
        }
    }
}
