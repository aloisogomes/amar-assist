<?php

namespace App\Imports;

use App\Dto\CreateFinanceData;
use App\Enums\FinanceType;
use App\Events\FinanceImportProgress;
use App\Repositories\Contracts\FinanceRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class FinanceImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithHeadingRow, WithValidation
{
    use Importable;

    private const int MAX_ERRORS = 50;

    public int $created = 0;

    public int $failed = 0;

    public int $processed = 0;

    public int $total = 0;

    /** @var list<array{row: int, messages: list<string>}> */
    public array $errors = [];

    public function __construct(
        private readonly FinanceRepositoryInterface $finances,
        private readonly int $userId,
        private readonly string $importId,
        private readonly int $chunkSize = 500,
    ) {}

    public function chunkSize(): int
    {
        return max(1, $this->chunkSize);
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event): void {
                $rows = $event->getReader()->getTotalRows();
                $this->total = max(0, (int) (array_values($rows)[0] ?? 0) - 1);
                $this->broadcastProgress();
            },
        ];
    }

    public function collection(Collection $collection): void
    {
        $batch = [];

        foreach ($collection as $row) {
            try {
                $batch[] = new CreateFinanceData(
                    description: trim((string) $row['description']),
                    amount: (int) round((float) $row['amount']),
                    date: (string) $row['date'],
                    type: FinanceType::fromSpreadsheet((string) $row['type']),
                );
            } catch (Throwable $exception) {
                $this->recordFailure(0, [$exception->getMessage()]);
            }
        }

        if ($batch !== []) {
            $this->created += $this->finances->createMany($batch);
        }

        $this->processed = $this->created + $this->failed;
        $this->broadcastProgress();
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function prepareForValidation(array $data, int $index): array
    {
        if (($data['date'] ?? null) instanceof CarbonInterface) {
            $data['date'] = $data['date']->format('Y-m-d');
        }

        if (isset($data['description'])) {
            $data['description'] = trim((string) $data['description']);
        }

        if (isset($data['type'])) {
            $data['type'] = trim((string) $data['type']);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', Rule::in(['income', 'expense', 'Receita', 'Despesa', 'receita', 'despesa'])],
        ];
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->recordFailure($failure->row(), $failure->errors());
        }
    }

    public function failedCount(): int
    {
        return $this->failed;
    }

    /**
     * @param  list<string>  $messages
     */
    private function recordFailure(int $row, array $messages): void
    {
        $this->failed++;

        if (count($this->errors) >= self::MAX_ERRORS) {
            return;
        }

        $this->errors[] = [
            'row' => $row,
            'messages' => $messages,
        ];
    }

    private function broadcastProgress(): void
    {
        event(new FinanceImportProgress(
            userId: $this->userId,
            importId: $this->importId,
            processed: $this->processed,
            total: $this->total,
            created: $this->created,
            failed: $this->failed,
            percent: $this->percent(),
        ));
    }

    private function percent(): int
    {
        if ($this->total <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->processed / $this->total) * 100));
    }
}
