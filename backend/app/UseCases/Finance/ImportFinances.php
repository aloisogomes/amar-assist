<?php

namespace App\UseCases\Finance;

use App\Dto\ImportFinanceResult;
use App\Jobs\ProcessFinanceImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\HeadingRowImport;

class ImportFinances
{
    /**
     * @var list<string>
     */
    private const REQUIRED_HEADINGS = ['date', 'description', 'amount', 'type'];

    public function handle(UploadedFile $file, int $userId): ImportFinanceResult
    {
        $this->assertHeadings($file);

        $path = $file->store('finance-imports');
        $importId = (string) Str::uuid();

        ProcessFinanceImport::dispatch($path, $userId, $importId);

        return new ImportFinanceResult(
            importId: $importId,
            message: 'Import queued.',
        );
    }

    private function assertHeadings(UploadedFile $file): void
    {
        $sheets = (new HeadingRowImport)->toArray($file);
        $headings = collect($sheets[0][0] ?? [])
            ->map(fn (mixed $heading): string => Str::lower(trim((string) $heading)))
            ->filter()
            ->values()
            ->all();

        $missing = array_values(array_diff(self::REQUIRED_HEADINGS, $headings));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => ['The spreadsheet must contain the columns: date, description, amount, type.'],
            ]);
        }
    }
}
