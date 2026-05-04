<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PdfDocumentController;

Route::get('/', [PdfDocumentController::class, 'index'])->name('documents.index');
Route::post('/documents', [PdfDocumentController::class, 'store'])->name('documents.store');
Route::get('/documents/{document}', [PdfDocumentController::class, 'show'])->name('documents.show');
