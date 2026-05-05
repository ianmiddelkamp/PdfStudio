<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PdfDocument extends Model
{
    protected $fillable = [
        'original_name',
        'stored_path',
        'page_count',
    ];

    protected $hidden = ['stored_path', 'error_message'];

    protected $casts = [
        'status' => DocumentStatus::class,
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
