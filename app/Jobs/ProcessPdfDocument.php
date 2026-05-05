<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\PdfDocument;
use App\Models\PdfField;
use App\Models\PdfPage;
use App\Services\PdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessPdfDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PdfDocument $document) {}

    public function handle(PdfService $pdfService): void
    {
        $this->document->status = DocumentStatus::Processing;
        $this->document->save();

        try {
            [$pageCount, $images] = $pdfService->rasterizeDocument($this->document->stored_path);

            DB::transaction(function () use ($pageCount, $images, $pdfService) {
                $pageIdLookup = [];

                foreach ($images as $pageNumber => $imagePath) {
                    $page = PdfPage::create([
                        'pdf_document_id' => $this->document->id,
                        'page_number'     => $pageNumber,
                        'image_path'      => $imagePath,
                    ]);
                    $pageIdLookup[$pageNumber] = $page->id;
                }

                $fields = $pdfService->extractFields($this->document->stored_path);

                foreach ($fields as $field) {
                    $pageId = $pageIdLookup[$field['page_number']] ?? null;

                    if ($pageId === null) {
                        continue;
                    }

                    PdfField::create([
                        'pdf_document_id' => $this->document->id,
                        'pdf_page_id'     => $pageId,
                        ...$field,
                    ]);
                }

                $this->document->page_count = $pageCount;
                $this->document->status = DocumentStatus::Ready;
                $this->document->save();
            });

        } catch (\Throwable $e) {
            $this->document->status = DocumentStatus::Failed;
            $this->document->error_message = $e->getMessage();
            $this->document->save();
            throw $e;
        }
    }
}
