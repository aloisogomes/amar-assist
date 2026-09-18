<?php

namespace App\Jobs;

use App\Events\FinanceImportCompleted;
use App\Imports\FinanceImport;
use App\Repositories\Contracts\FinanceRepositoryInterface;
use App\Support\FinanceDashboardCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessFinanceImport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public string $path,
        public int $userId,
        public string $importId,
        public int $chunkSize = 500,
    ) {}

    public function handle(FinanceRepositoryInterface $finances, FinanceDashboardCache $dashboardCache): void
    {
        $import = new FinanceImport(
            $finances,
            $this->userId,
            $this->importId,
            $this->chunkSize,
        );

        Excel::import($import, $this->path, 'local');

        $dashboardCache->bump();

        event(new FinanceImportCompleted(
            userId: $this->userId,
            importId: $this->importId,
            created: $import->created,
            failed: $import->failedCount(),
            errors: $import->errors,
        ));

        Storage::disk('local')->delete($this->path);
    }

    public function failed(?Throwable $exception): void
    {
        event(new FinanceImportCompleted(
            userId: $this->userId,
            importId: $this->importId,
            created: 0,
            failed: 0,
            errors: [[
                'row' => 0,
                'messages' => [$exception?->getMessage() ?? 'Import failed.'],
            ]],
        ));

        Storage::disk('local')->delete($this->path);
    }
}
