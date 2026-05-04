<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfDocument extends Model
{
    protected $fillable = [
        'original_name',
        'stored_path',
        'page_count',
    ];
}
