<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Service\Import\CodeNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CodeNormalizerTest extends TestCase
{
    #[DataProvider('skillCodes')]
    public function testNormalizesSkillCodes(string $input, string $expected): void
    {
        self::assertSame($expected, (new CodeNormalizer())->normalizeSkillCode('39765', 'BC01', $input));
    }

    public static function skillCodes(): iterable
    {
        yield 'short unpadded' => ['C4', 'RNCP39765-BC01-C04'];
        yield 'short padded' => ['C04', 'RNCP39765-BC01-C04'];
        yield 'whitespace and case' => [' c5 ', 'RNCP39765-BC01-C05'];
        yield 'block qualified' => ['BC02-C4', 'RNCP39765-BC02-C04'];
        yield 'fully qualified' => ['RNCP39765-BC02-C4', 'RNCP39765-BC02-C04'];
        yield 'already canonical' => ['RNCP39765-BC02-C04', 'RNCP39765-BC02-C04'];
        yield 'two digits' => ['C21', 'RNCP39765-BC01-C21'];
        yield 'empty' => ['', ''];
    }
}
