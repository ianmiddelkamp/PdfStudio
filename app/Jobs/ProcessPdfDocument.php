<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\PdfDocument;
use App\Models\PdfField;
use App\Models\PdfPage;
use App\Services\PdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPdfDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PdfDocument $document) {}

    public function handle(PdfService $pdfService): void
    {
        $this->document->update(['status' => DocumentStatus::Processing]);

        try {
            [$pageCount, $images] = $pdfService->rasterizeDocument($this->document->stored_path);

            foreach ($images as $pageNumber => $imagePath) {
                PdfPage::create([
                    'pdf_document_id' => $this->document->id,
                    'page_number'     => $pageNumber,
                    'image_path'      => $imagePath,
                ]);
            }

            $fields = $pdfService->extractFields($this->document->stored_path);

            foreach ($fields as $field) {
                PdfField::create([
                    'pdf_document_id' => $this->document->id,
                    ...$field,
                ]);
            }

            $this->document->update([
                'page_count' => $pageCount,
                'status'     => DocumentStatus::Ready,
            ]);
        } catch (\Throwable $e) {
            $this->document->update(['status' => DocumentStatus::Failed]);
            throw $e;
        }
    }
}
