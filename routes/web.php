<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PdfDocumentController;

//document routes
Route::get('/', [PdfDocumentController::class, 'index'])->name('documents.index');
Route::post('/documents', [PdfDocumentController::class, 'store'])->name('documents.store');
Route::get('/documents/{document}', [PdfDocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{document}/status', [PdfDocumentController::class, 'status'])->name('documents.status');
Route::get('/documents/{document}/pages/{page}/image', [PdfDocumentController::class, 'getPageImage'])->name('documents.page_image');
Route::delete('/documents/{document}', [PdfDocumentController::class, 'destroy'])->name('documents.destroy');
