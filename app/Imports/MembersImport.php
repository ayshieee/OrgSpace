<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Reads the roster template columns positionally — First Name | M.I. |
 * Surname | Student ID | Email — rather than relying on heading-row key
 * normalization (which mangles a header like "M.I." unpredictably).
 * Row 0 (the header) is always skipped.
 */
class MembersImport implements ToCollection
{
    public array $rows = [];

    public function collection(Collection $rows): void
    {
        $this->rows = $rows->skip(1)
            ->filter(fn ($row) => collect($row)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->map(fn ($row) => [
                'first_name' => trim((string) ($row[0] ?? '')),
                'm_i' => trim((string) ($row[1] ?? '')),
                'surname' => trim((string) ($row[2] ?? '')),
                'student_id' => trim((string) ($row[3] ?? '')),
                'email' => trim((string) ($row[4] ?? '')),
            ])
            ->values()
            ->all();
    }
}
