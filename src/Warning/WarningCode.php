<?php

declare(strict_types=1);

namespace App\Warning;

enum WarningCode: string
{
    case ContentFileMissing = 'content_file_missing';
    case ContentJsonInvalid = 'content_json_invalid';
}
