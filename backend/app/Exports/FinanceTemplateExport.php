<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinanceTemplateExport implements FromArray, WithHeadings
{
    use Exportable;

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['date', 'description', 'amount', 'type'];
    }

    /**
     * @return list<list<string|int>>
     */
    public function array(): array
    {
        return [
            ['2026-05-01', 'Mensalidade Cliente B #869', 115346, 'Receita'],
        ];
    }
}
