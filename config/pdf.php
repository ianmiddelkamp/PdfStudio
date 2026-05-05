<?php

return [
    'pdftk' => env('PDF_PDFTK_PATH', base_path('bin/pdftk.exe')),
    'gs'    => env('PDF_GS_PATH', 'gs'),
];
