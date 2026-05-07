<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPdfDocument;
use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\PdfFill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PdfDocumentController extends Controller
{
    public function index()
    {
        $documents = PdfDocument::latest()->get();
        return Inertia::render('Documents/Index', [
            'documents' => $documents,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $path = $request->file('pdf')->store('pdfs', 'local');

        try {
            $document = PdfDocument::create([
                'original_name' => $request->file('pdf')->getClientOriginalName(),
                'stored_path'   => $path,
            ]);

            ProcessPdfDocument::dispatch($document);
        } catch (\Throwable) {
            return redirect()->back()->withErrors(['pdf' => 'Failed to process document.']);
        }

        return redirect()->route('documents.show', $document);
    }

    public function destroy(PdfDocument $document)
    {
        Storage::disk('local')->delete($document->stored_path);

        foreach ($document->pages as $page) {
            @unlink($page->image_path);
        }

        $document->delete();

        return redirect()->route('documents.index');
    }

    public function status(PdfDocument $document)
    {
        return response()->json(['status' => $document->status]);
    }

    public function edit(PdfDocument $document)
    {
        $document->load('pages.fields');
        $document->setRelation('defaultFill', $document->pdfFills()->where('name', PdfFill::DEFAULT_NAME)->with('fillValues')->first());
        $defaultFill = $document->defaultFill;

        $valueMap = $defaultFill 
            ? $defaultFill->fillValues->keyBy('pdf_field_id')
            : collect();

        $document->unsetRelation('defaultFill');

        foreach ($document->pages as $page) {
            foreach ($page->fields as $field) {
                $field->value = $valueMap->get($field->id)?->value ?? '';
            }
        }
        return Inertia::render('Documents/Edit', [
            'document' => $document
        ]);
    }


    public function show(PdfDocument $document)
    {
        return Inertia::render('Documents/Show', [
            'document' => $document->load('pages.fields'),
        ]);
    }

    public function getPageImage(PdfDocument $document, PdfPage $page)
    {
        if ($page->pdf_document_id !== $document->id || !file_exists($page->image_path)) {
            abort(404);
        }

        return response()->file($page->image_path, [
            'Content-Type' => 'image/png',
        ]);
    }
}
