<?php

namespace App\Service\Admin;

final class DatasetAlreadyExistsException extends \RuntimeException
{
    public function __construct(
        public readonly string $datasetName,
    ) {
        parent::__construct(sprintf('Le dossier "%s" existe déjà.', $datasetName));
    }
}
