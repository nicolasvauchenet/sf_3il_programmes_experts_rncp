<?php

declare(strict_types=1);

namespace App\Service\Framework;

use App\Warning\Warning;
use App\Warning\WarningCollector;

final readonly class EvaluationReader
{
    private const TYPE = 'evaluations';

    public function __construct(
        private DatasetPathResolver $pathResolver,
        private CachedJsonResourceLoader $jsonLoader,
        private WarningCollector $warnings,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readOne(FrameworkContext $context, string $code): ?array
    {
        $path = $this->pathResolver->contentPath($context, self::TYPE, $code);

        $result = $this->jsonLoader->load($path);

        if ($result->exists === false) {
            $this->warnings->add(Warning::contentFileMissing($path, [
                'type' => self::TYPE,
                'code' => strtolower(trim($code)),
                'promotion' => $context->promotion,
                'year' => $context->year,
            ]));

            return null;
        }

        if ($result->ok === false) {
            $this->warnings->add(Warning::contentJsonInvalid($path, (string)$result->error, [
                'type' => self::TYPE,
                'code' => strtolower(trim($code)),
                'promotion' => $context->promotion,
                'year' => $context->year,
            ]));

            return null;
        }

        $data = $result->data;

        if (!is_array($data) || array_is_list($data)) {
            $this->warnings->add(Warning::contentJsonInvalid($path, 'Expected a JSON object.', [
                'type' => self::TYPE,
                'code' => strtolower(trim($code)),
                'promotion' => $context->promotion,
                'year' => $context->year,
            ]));

            return null;
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param list<string> $codes
     * @return list<array<string, mixed>>
     */
    public function readMany(FrameworkContext $context, array $codes): array
    {
        $out = [];

        foreach ($codes as $code) {
            $item = $this->readOne($context, (string)$code);
            if ($item !== null) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
