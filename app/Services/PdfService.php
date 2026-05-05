<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class PdfService
{
    public function __construct(private FdfService $fdfService) {}

    public function absolutePath(string $storedPath): string
    {
        return Storage::disk('local')->path($storedPath);
    }

    /**
     * Extract form fields from a PDF using PyMuPDF.
     * Returns an array of field data ready for PdfField::create().
     */
    public function extractFields(string $storedPath): array
    {
        $absolutePath = $this->absolutePath($storedPath);

        $process = new Process([
            config('pdf.python'),
            base_path('python/extract_pdf_fields.py'),
            $absolutePath,
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $data = json_decode($process->getOutput(), true);

        if (!$data || empty($data['fields'])) {
            return [];
        }

        return array_map(function (array $field) use ($storedPath) {
            $css = $field['css'];
            return [
                'field_name'       => $field['name'],
                'field_type'       => $field['type'],
                'page_number'      => $field['page'],
                'pdf_left'         => $css['left'],
                'pdf_top'          => $css['top'],
                'pdf_width'        => $css['width'],
                'pdf_height'       => $css['height'],
                'css_left'         => $css['left'],
                'css_top'          => $css['top'],
                'css_width'        => $css['width'],
                'css_height'       => $css['height'],
                'font'             => $css['font'] ?? null,
                'font_size'        => $css['font-size'] ?? null,
                'text_color'       => $this->colorToCss($css['text-color'] ?? null),
                'background_color' => $this->colorToCss($css['background-color'] ?? null),
                'border_color'     => $this->colorToCss($css['border-color'] ?? null),
                'border_style'     => $css['border-style'] ?? null,
                'border_width'     => $css['border-width'] ?? null,
            ];
        }, $data['fields']);
    }

    private function colorToCss(mixed $color): ?string
    {
        if (!is_array($color) || count($color) < 3) {
            return null;
        }

        [$r, $g, $b] = $color;
        return sprintf('rgb(%d, %d, %d)', $r * 255, $g * 255, $b * 255);
    }

    /**
     * Burst, flatten, and rasterize all pages of a PDF.
     * Returns an array of absolute image paths keyed by 1-based page number.
     */
    public function rasterizeDocument(string $storedPath): array
    {
        $absolutePath = $this->absolutePath($storedPath);
        $pageCount    = $this->getPageCount($absolutePath);

        if ($pageCount === 0) {
            return [];
        }

        $tempDir   = storage_path('app/temp/' . uniqid('pdf_', true));
        $outputDir = storage_path('app/page-images/' . md5($storedPath));

        mkdir($tempDir, 0755, true);
        mkdir($outputDir, 0755, true);

        $process = new Process(
            [config('pdf.pdftk'), $absolutePath, 'burst', 'output', $tempDir . '/Page%d.pdf'],
            cwd: base_path('bin')
        );
        $process->run();

        $images = [];

        for ($i = 1; $i <= $pageCount; $i++) {
            $pagePdf   = $tempDir . "/Page{$i}.pdf";
            $imageFile = $outputDir . "/page{$i}.png";

            if (!file_exists($pagePdf)) {
                continue;
            }

            $pagePdf = $this->flattenPageFields($pagePdf, $tempDir, $i);
            $this->rasterizeToImage($pagePdf, $imageFile);

            if (file_exists($imageFile)) {
                @unlink($pagePdf);
                $images[$i] = $imageFile;
            }
        }

        // Remove doc_data.txt that pdftk burst emits
        @unlink($tempDir . '/doc_data.txt');
        @rmdir($tempDir);

        return $images;
    }

    private function getPageCount(string $absolutePath): int
    {
        $process = new Process([config('pdf.pdftk'), $absolutePath, 'dump_data'], cwd: base_path('bin'));
        $process->run();

        foreach (explode("\n", $process->getOutput()) as $line) {
            [$key, $value] = array_pad(explode(': ', trim($line), 2), 2, null);
            if ($key === 'NumberOfPages') {
                return (int) $value;
            }
        }

        return 0;
    }

    private function flattenPageFields(string $pagePdf, string $tempDir, int $pageNum): string
    {
        $process = new Process([config('pdf.pdftk'), $pagePdf, 'dump_data_fields'], cwd: base_path('bin'));
        $process->run();

        $fieldNames = [];
        foreach (explode("\n", $process->getOutput()) as $line) {
            [$key, $value] = array_pad(explode(': ', trim($line), 2), 2, null);
            if ($key === 'FieldName') {
                $fieldNames[$value] = '';
            }
        }

        if (empty($fieldNames)) {
            return $pagePdf;
        }

        $fdfPath    = $tempDir . "/page{$pageNum}.fdf";
        $filledPdf  = $tempDir . "/page{$pageNum}_filled.pdf";
        $flatPdf    = $tempDir . "/page{$pageNum}_flattened.pdf";

        file_put_contents($fdfPath, $this->fdfService->create($pagePdf, $fieldNames));

        $process = new Process(
            [config('pdf.pdftk'), $pagePdf, 'fill_form', $fdfPath, 'output', $filledPdf, 'verbose'],
            cwd: base_path('bin')
        );
        $process->run();

        @unlink($fdfPath);

        if (!file_exists($filledPdf)) {
            return $pagePdf;
        }

        @unlink($pagePdf);

        $process = new Process(
            [config('pdf.pdftk'), $filledPdf, 'output', $flatPdf, 'flatten'],
            cwd: base_path('bin')
        );
        $process->run();

        @unlink($filledPdf);

        return file_exists($flatPdf) ? $flatPdf : $pagePdf;
    }

    private function rasterizeToImage(string $pdfPath, string $outputPath): void
    {
        $process = new Process([
            config('pdf.magick'),
            '-colorspace', 'SRGB',
            '-density', '700',
            $pdfPath,
            '-background', 'white',
            '-alpha', 'remove',
            '-flatten',
            '-quality', '30',
            $outputPath,
        ]);
        $process->run();
    }

    private function parseFieldDump(string $output): array
    {
        $fields  = [];
        $current = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);

            if ($line === '---') {
                if (!empty($current)) {
                    $fields[]  = $current;
                    $current   = [];
                }
                continue;
            }

            if (str_contains($line, ': ')) {
                [$key, $value] = explode(': ', $line, 2);
                $current[$key] = $value;
            }
        }

        if (!empty($current)) {
            $fields[] = $current;
        }

        return $fields;
    }
}
