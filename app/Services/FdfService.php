<?php

namespace App\Services;

class FdfService
{
    public function create(string $pdfPath, array $fields): string
    {
        $data = "%FDF-1.2\n%\xe2\xe3\xcf\xd3\n1 0 obj\n<< \n/FDF << /Fields [ ";

        foreach ($fields as $field => $val) {
            if (is_array($val)) {
                $data .= '<</T(' . $field . ')/V[';
                foreach ($val as $opt) {
                    $opt = str_replace(['(', ')'], ['\(', '\)'], (string) $opt);
                    $data .= '(' . trim($opt) . ')';
                }
                $data .= ']>>';
            } else {
                $val = str_replace(['(', ')'], ['\(', '\)'], (string) $val);
                $data .= '<</T(' . $field . ')/V(' . trim($val) . ')>>';
            }
        }

        $data .= "] \n/F (" . $pdfPath . ") /ID [ <" . md5((string) time()) . ">\n] >> \n>> \nendobj\ntrailer\n<<\n/Root 1 0 R \n\n>>\n%%EOF\n";

        return $data;
    }
}
