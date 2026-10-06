<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Pdf;

use App\Service\Pdf\PdfGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class PdfGeneratorTest extends TestCase
{
    public function testBrandFontsAreEmbeddedInsteadOfSilentlyUsingSystemFonts(): void
    {
        $projectDir = dirname(__DIR__, 4);
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($projectDir);
        $kernel->method('getCacheDir')->willReturn($projectDir . '/var/cache/test/pdf-brand-test');

        $html = <<<'HTML'
            <!DOCTYPE html><html><head><meta charset="utf-8">
            <style>
                body { font-family: Montserrat; }
                h1 { font-family: "Bebas Neue"; font-weight: normal; }
            </style></head><body>
            <h1>INGÉNIERIE ET COMPÉTENCES</h1>
            <p>Évaluation des compétences.</p><strong>Texte gras.</strong><em>Texte italique.</em>
            </body></html>
            HTML;

        $response = (new PdfGenerator($kernel))->download($html, 'fiche.pdf', [
            'promotionTitle' => 'CDWFS',
            'sheetLabel' => 'Fiche matière',
            'sheetTitle' => 'Ingénierie et compétences',
        ]);

        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        $pdf = $response->getContent();
        self::assertIsString($pdf);
        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertMatchesRegularExpression('/\/BaseFont\s+\/[^\s]*Montserrat/', $pdf);
        self::assertMatchesRegularExpression('/\/BaseFont\s+\/[^\s]*BebasNeue/', $pdf);
        self::assertStringContainsString('/FontFile2', $pdf);
        self::assertStringNotContainsString('/BaseFont /Helvetica', $pdf);
        self::assertStringNotContainsString('/BaseFont /Times', $pdf);
    }
}
