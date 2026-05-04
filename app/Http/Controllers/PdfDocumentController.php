<?php

namespace App\Http\Controllers;

use App\Models\PdfDocument;
use App\Services\PdfService;
use Illuminate\Http\Request;

class PdfDocumentController extends Controller
{
    public function __construct(private PdfService $pdfService) {}
    public function index()
    {
        $documents = PdfDocument::latest()->get();
        return view('documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $path = $request->file('pdf')->store('pdfs', 'local');

        $document = PdfDocument::create([
            'original_name' => $request->file('pdf')->getClientOriginalName(),
            'stored_path'   => $path,
        ]);

        return redirect()->route('documents.show', $document);
    }

    public function show(PdfDocument $document)
    {
        $fields = $this->pdfService->extractFormFields($document->stored_path);

        return view('documents.show', compact('document', 'fields'));
    }
}
