<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfField extends Model
{
    protected $guarded = [];

    protected $casts = [
        'page_number' => 'integer',
        'pdf_left' => 'float',
        'pdf_top' => 'float',
        'pdf_width' => 'float',
        'pdf_height' => 'float',
        'css_left' => 'float',
        'css_top'  => 'float',
        'css_width'  => 'float',
        'css_height'  => 'float',
        'font_size'  => 'float',
        'border_width' => 'float',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class, 'pdf_document_id');
    }

     public function page(): BelongsTo
    {
        return $this->belongsTo(PdfPage::class, 'pdf_page_id');
    }
}
