<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class PdfService
{
    /**
     * Get the absolute path to a stored PDF.
     */
    public function absolutePath(string $storedPath): string
    {
        return Storage::disk('local')->path($storedPath);
    }

    /**
     * Extract form fields using pdftk.
     * Returns an array of field data.
     */
    public function extractFormFields(string $storedPath): array
    {
        $absolutePath = $this->absolutePath($storedPath);

        $process = new Process(['pdftk', $absolutePath, 'dump_data_fields']);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        return $this->parseFieldDump($process->getOutput());
    }

    /**
     * Parse pdftk dump_data_fields output into an array.
     */
    private function parseFieldDump(string $output): array
    {
        $fields = [];
        $current = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);

            if ($line === '---') {
                if (!empty($current)) {
                    $fields[] = $current;
                    $current = [];
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

    /**
     * Rasterize a single PDF page to a JPEG using Ghostscript.
     * Returns the absolute path to the output image.
     */
    public function rasterizePage(string $storedPath, int $page, int $dpi = 150): string
    {
        $absolutePath = $this->absolutePath($storedPath);
        $outputDir    = storage_path('app/page-images');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputFile = $outputDir . '/' . md5($storedPath) . "_page{$page}.jpg";

        if (!file_exists($outputFile)) {
            $process = new Process([
                'gs',
                '-dNOPAUSE', '-dBATCH', '-dSAFER',
                '-sDEVICE=jpeg',
                "-r{$dpi}",
                "-dFirstPage={$page}",
                "-dLastPage={$page}",
                "-sOutputFile={$outputFile}",
                $absolutePath,
            ]);
            $process->run();
        }

        return $outputFile;
    }
}