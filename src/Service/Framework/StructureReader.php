<?php

declare(strict_types=1);

namespace App\Service\Framework;

use App\Exception\InvalidStructureJsonException;

final readonly class StructureReader
{
    public function __construct(
        private DatasetPathResolver $pathResolver,
        private CachedJsonResourceLoader $jsonLoader,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function read(FrameworkContext $context): array
    {
        $path = $this->pathResolver->structurePath($context);
        $result = $this->jsonLoader->load($path);

        if ($result->exists === false) {
            throw InvalidStructureJsonException::decodeError(
                $path,
                'structure.json unexpectedly missing after resolution.'
            );
        }

        if ($result->ok === false) {
            throw InvalidStructureJsonException::decodeError($path, (string)$result->error);
        }

        $data = $result->data;

        if (!is_array($data) || array_is_list($data)) {
            throw InvalidStructureJsonException::notAnObject($path);
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
