<?php

return [
    'pdftk'  => env('PDF_PDFTK_PATH', base_path('bin/pdftk.exe')),
    'gs'     => env('PDF_GS_PATH', 'gs'),
    'magick' => env('PDF_MAGICK_PATH', 'magick'),
    'python' => env('PDF_PYTHON_PATH', 'python'),
];
