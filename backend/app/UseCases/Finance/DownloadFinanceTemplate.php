<?php

namespace App\UseCases\Finance;

use App\Exports\FinanceTemplateExport;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadFinanceTemplate
{
    public function handle(): BinaryFileResponse
    {
        return (new FinanceTemplateExport)->download(
            'financial_transactions.xlsx',
            Excel::XLSX,
        );
    }
}
