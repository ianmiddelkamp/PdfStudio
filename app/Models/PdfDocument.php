<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PdfField;
use App\Models\PdfPage;
use App\Enums\DocumentStatus;

class PdfDocument extends Model
{
    protected $fillable = [
        'original_name',
        'stored_path',
        'page_count',
    ];

    protected $casts = [
        'status' => DocumentStatus::class
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(PdfPage::class, 'pdf_document_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PdfField::class, 'pdf_document_id');
    }
}
