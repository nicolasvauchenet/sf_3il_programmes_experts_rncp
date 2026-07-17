<?php

namespace App\Service\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class PdfGenerator
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * @param array{promotionTitle:string, sheetLabel:string, sheetTitle:string} $header
     */
    public function download(string $html, string $filename, array $header): Response
    {
        $html = $this->prepareHtml($html);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $boldFont = $fontMetrics->getFont('DejaVu Sans', 'bold');
        $logoPath = $this->kernel->getProjectDir() . '/assets/img/logo-3il-pdf.jpg';

        $canvas->page_script(static function (
            int $pageNumber,
            int $pageCount,
            mixed $canvas,
            mixed $fontMetrics,
        ) use ($font, $boldFont, $header, $logoPath): void {
            $pageWidth = $canvas->get_width();
            $primaryColor = [0.16, 0.36, 0.44];
            $headerSideMargin = 43;

            $canvas->filled_rectangle(0, 0, $pageWidth, 88, [1, 1, 1]);
            $canvas->image($logoPath, $headerSideMargin, 23, 230, 22.2);

            $drawRightAlignedText = static function (
                string $text,
                float $y,
                float $fontSize,
            ) use (
                $canvas,
                $fontMetrics,
                $boldFont,
                $pageWidth,
                $primaryColor,
                $headerSideMargin,
            ): void {
                $textWidth = $fontMetrics->getTextWidth($text, $boldFont, $fontSize);
                $canvas->text(
                    $pageWidth - $headerSideMargin - $textWidth,
                    $y,
                    $text,
                    $boldFont,
                    $fontSize,
                    $primaryColor,
                );
            };

            $drawRightAlignedText($header['promotionTitle'], 14, 8);
            $drawRightAlignedText($header['sheetLabel'], 28.5, 7.5);

            $titleFontSize = 11.0;
            $titleMaxWidth = 270.0;
            $titleLines = [];

            do {
                $titleLines = [];
                $currentLine = '';

                foreach (preg_split('/\s+/', trim($header['sheetTitle'])) ?: [] as $word) {
                    $candidate = $currentLine === '' ? $word : $currentLine . ' ' . $word;

                    if (
                        $currentLine !== ''
                        && $fontMetrics->getTextWidth($candidate, $boldFont, $titleFontSize) > $titleMaxWidth
                    ) {
                        $titleLines[] = $currentLine;
                        $currentLine = $word;
                    } else {
                        $currentLine = $candidate;
                    }
                }

                if ($currentLine !== '') {
                    $titleLines[] = $currentLine;
                }

                if (count($titleLines) > 3) {
                    $titleFontSize -= 0.5;
                }
            } while (count($titleLines) > 3 && $titleFontSize >= 8);

            $titleLineHeight = $titleFontSize + 2.5;
            $titleStartY = match (count($titleLines)) {
                1 => 48.0,
                2 => 44.5,
                default => 41.5,
            };

            foreach ($titleLines as $index => $titleLine) {
                $drawRightAlignedText(
                    $titleLine,
                    $titleStartY + ($index * $titleLineHeight),
                    $titleFontSize,
                );
            }

            $ruleColor = [0.55, 0.65, 0.69];
            $canvas->line(
                $headerSideMargin,
                86,
                $pageWidth - $headerSideMargin,
                86,
                $ruleColor,
                0.6,
            );

            $label = sprintf('Page %d/%d', $pageNumber, $pageCount);
            $fontSize = 8;
            $rightMargin = 31;
            $bottomMargin = 23;
            $textWidth = $fontMetrics->getTextWidth($label, $font, $fontSize);

            $canvas->line(
                $headerSideMargin,
                $canvas->get_height() - 34,
                $pageWidth - $headerSideMargin,
                $canvas->get_height() - 34,
                $ruleColor,
                0.6,
            );

            $canvas->text(
                $canvas->get_width() - $rightMargin - $textWidth,
                $canvas->get_height() - $bottomMargin,
                $label,
                $font,
                $fontSize,
                [0.25, 0.25, 0.25],
            );
        });

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $this->sanitizeFilename($filename)),
        ]);
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $filename) ?? 'fiche.pdf';

        return trim($filename, '-') ?: 'fiche.pdf';
    }

    private function prepareHtml(string $html): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $xpath = new \DOMXPath($document);

        $links = [];
        foreach ($xpath->query('//a') ?: [] as $link) {
            $links[] = $link;
        }

        foreach ($links as $link) {
            $parent = $link->parentNode;
            if ($parent === null) {
                continue;
            }

            while ($link->firstChild !== null) {
                $parent->insertBefore($link->firstChild, $link);
            }

            $parent->removeChild($link);
        }

        foreach ($xpath->query(
            '//section[section[contains(concat(" ", normalize-space(@class), " "), " app-section ")]]'
        ) ?: [] as $section) {
            $classes = trim($section->getAttribute('class') . ' pdf-section-parent');
            $section->setAttribute('class', $classes);
        }

        return $document->saveHTML() ?: $html;
    }
}
