<?php

declare(strict_types=1);

namespace App\Service\Framework;

use App\Exception\DatasetNotFoundException;

final readonly class DatasetCatalog
{
    public function __construct(
        private string $datasetRootPath,
    ) {
    }

    /**
     * @return array{
     *   promotions: list<string>,
     *   yearsByPromotion: array<string, list<string>>,
     *   datasets: list<array{promotion:string, year:string, dir:string}>
     * }
     */
    public function listAvailable(): array
    {
        $root = rtrim($this->datasetRootPath, '/');

        if (!is_dir($root)) {
            throw DatasetNotFoundException::rootNotFound($root);
        }

        $entries = @scandir($root);
        if ($entries === false) {
            throw DatasetNotFoundException::rootNotFound($root);
        }

        $datasets = [];
        $yearsByPromotion = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $dir = $root.'/'.$entry;
            if (!is_dir($dir)) {
                continue;
            }

            if (!preg_match('/^([a-z0-9_]{2,50})_(\d{4}(?:-\d{4})?)$/', $entry, $m)) {
                continue;
            }

            $promotion = $m[1];
            $year = $m[2];

            if (!is_file($dir.'/structure.json')) {
                continue;
            }

            $datasets[] = [
                'promotion' => $promotion,
                'year' => $year,
                'dir' => $dir,
            ];

            $yearsByPromotion[$promotion] ??= [];
            $yearsByPromotion[$promotion][] = $year;
        }

        $promotions = array_keys($yearsByPromotion);
        sort($promotions);

        foreach ($yearsByPromotion as $p => $years) {
            $years = array_values(array_unique($years));
            rsort($years);
            $yearsByPromotion[$p] = $years;
        }

        usort($datasets, static function (array $a, array $b): int {
            if ($a['promotion'] === $b['promotion']) {
                return strcmp($b['year'], $a['year']);
            }

            return strcmp($a['promotion'], $b['promotion']);
        });

        return [
            'promotions' => $promotions,
            'yearsByPromotion' => $yearsByPromotion,
            'datasets' => $datasets,
        ];
    }
}
