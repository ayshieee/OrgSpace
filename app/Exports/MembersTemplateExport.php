<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MembersTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['Juan', 'D', 'Dela Cruz', '2023-00123', 'juan.delacruz@example.edu'],
        ];
    }

    public function headings(): array
    {
        return ['First Name', 'M.I.', 'Surname', 'Student ID', 'Email'];
    }
}
