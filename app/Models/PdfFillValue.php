<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfFillValue extends Model
{
    public $guarded = [];
    //
    public function pdfFill(): BelongsTo
    {
        return $this->belongsTo(PdfFill::class, 'pdf_fill_id');
    }
     public function pdfField(): BelongsTo
    {
        return $this->belongsTo(PdfField::class, 'pdf_field_id');
    }
}
