<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PdfFill extends Model
{
    public const DEFAULT_NAME = "Default";
    public $guarded = [];
    //
    public function document(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class, 'pdf_document_id');
    }

    public function fillValues(): HasMany
    {
        return $this->hasMany(PdfFillValue::class, 'pdf_fill_id');
    }
}
