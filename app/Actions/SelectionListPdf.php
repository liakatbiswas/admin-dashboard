<?php

namespace App\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SelectionListPdf
{
    public function render(Collection $applications): string
    {
        $lines = [];
        foreach ($applications as $application) {
            $line = $application->number.' | '.$application->name_en.' | '.$application->grade.' | '.$application->selected_shift;
            foreach (explode("\n", wordwrap(Str::ascii($line), 85, "\n", true)) as $part) {
                $lines[] = $part;
            }
        }
        if ($applications->isEmpty()) {
            $lines[] = 'No published selections.';
        }
        $pages = array_chunk($lines, 45);
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 2 => '', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>'];
        $kids = [];
        foreach ($pages as $index => $page) {
            $page = ['Nasirabad Girls High School - Selected applicants',
                'Application number | Name | Class | Selected shift', ...$page];
            $pageId = 4 + $index * 2;
            $streamId = $pageId + 1;
            $kids[] = $pageId.' 0 R';
            $stream = "BT /F1 10 Tf 36 800 Td 15 TL\n";
            foreach ($page as $line) {
                $line = preg_replace('/[^\x20-\x7E]/', '', $line);
                $line = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
                $stream .= '('.$line.") Tj T*\n";
            }
            $stream .= 'ET';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$streamId.' 0 R >>';
            $objects[$streamId] = '<< /Length '.strlen($stream).">>\nstream\n".$stream."\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Count '.count($kids).' /Kids ['.implode(' ', $kids).'] >>';
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n".$xref."\n%%EOF";
    }
}
