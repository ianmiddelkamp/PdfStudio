<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PdfPage extends Model
{
    protected $guarded = [];

    protected $hidden = ['image_path'];

    protected $casts = [
        'page_number' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class, 'pdf_document_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PdfField::class, 'pdf_page_id');
    }
}
