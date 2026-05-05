<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class EnvironmentService
{
    public function check(): array
    {
        return [
            'pdftk'  => $this->checkTool(config('pdf.pdftk'), ['--version']),
            'gs'     => $this->checkTool(config('pdf.gs'), ['--version']),
            'magick' => $this->checkTool(config('pdf.magick'), ['--version']),
            'python' => $this->checkTool(config('pdf.python'), ['--version']),
        ];
    }

    public function pdftkAvailable(): bool
    {
        return $this->checkTool(config('pdf.pdftk'), ['--version']);
    }

    public function gsAvailable(): bool
    {
        return $this->checkTool(config('pdf.gs'), ['--version']);
    }

    public function magickAvailable(): bool
    {
        return $this->checkTool(config('pdf.magick'), ['--version']);
    }

    public function pythonAvailable(): bool
    {
        return $this->checkTool(config('pdf.python'), ['--version']);
    }

    private function checkTool(string $binary, array $args): bool
    {
        $process = new Process([$binary, ...$args], cwd: $this->binDir($binary));

        try {
            $process->run();
            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function binDir(string $binary): ?string
    {
        // Run from bin/ so Windows DLL dependencies (e.g. libiconv2.dll) are found
        $binDir = base_path('bin');
        return str_starts_with(realpath($binary) ?: '', $binDir) ? $binDir : null;
    }
}
