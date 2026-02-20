<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidStructureJsonException extends FrameworkException
{
    public static function decodeError(string $path, string $message): self
    {
        return new self(sprintf('Invalid structure.json (%s): %s', $path, $message));
    }

    public static function notAnObject(string $path): self
    {
        return new self(sprintf('Invalid structure.json (%s): expected a JSON object.', $path));
    }
}
