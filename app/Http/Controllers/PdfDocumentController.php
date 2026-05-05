<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPdfDocument;
use App\Models\PdfDocument;
use Illuminate\Http\Request;
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

    public function show(PdfDocument $document)
    {
        return Inertia::render('Documents/Show', [
            'document' => $document->load('pages', 'fields'),
        ]);
    }
}
