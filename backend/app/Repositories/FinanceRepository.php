<?php

namespace App\Repositories;

use App\Dto\CreateFinanceData;
use App\Dto\ListFinancesQuery;
use App\Dto\UpdateFinanceData;
use App\Enums\FinanceType;
use App\Models\Finance;
use App\Repositories\Contracts\FinanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceRepository implements FinanceRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Finance>
     */
    public function paginate(ListFinancesQuery $query): LengthAwarePaginator
    {
        return Finance::query()
            ->when($query->q, function ($builder) use ($query): void {
                $escaped = addcslashes($query->q, '%_\\');
                $builder->where('description', 'like', '%'.$escaped.'%');
            })
            ->when($query->type, fn ($builder) => $builder->where('type', $query->type))
            ->when($query->from, fn ($builder) => $builder->whereDate('date', '>=', $query->from))
            ->when($query->to, fn ($builder) => $builder->whereDate('date', '<=', $query->to))
            ->when($query->minAmount !== null, fn ($builder) => $builder->where('amount', '>=', $query->minAmount))
            ->when($query->maxAmount !== null, fn ($builder) => $builder->where('amount', '<=', $query->maxAmount))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($query->perPage);
    }

    public function findByUuid(string $uuid): ?Finance
    {
        return Finance::query()->where('uuid', $uuid)->first();
    }

    public function create(CreateFinanceData $data): Finance
    {
        return Finance::query()->create($data->toArray());
    }

    /**
     * @param  list<CreateFinanceData>  $rows
     */
    public function createMany(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $now = now();
        $payload = array_map(fn (CreateFinanceData $data): array => [
            'uuid' => (string) Str::uuid(),
            'description' => $data->description,
            'amount' => $data->amount,
            'date' => $data->date,
            'type' => $data->type->value,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);

        DB::transaction(function () use ($payload): void {
            Finance::query()->insert($payload);
        });

        return count($payload);
    }

    public function update(Finance $finance, UpdateFinanceData $data): Finance
    {
        $finance->update($data->toArray());

        return $finance->refresh();
    }

    public function delete(Finance $finance): void
    {
        $finance->delete();
    }

    /**
     * @return Collection<int, object{date: string, type: string, total: int, count: int}>
     */
    public function summarizeByPeriod(string $from, string $to): Collection
    {
        return DB::table('finances')
            ->selectRaw('date, type, SUM(amount) as total, COUNT(*) as count')
            ->whereNull('deleted_at')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->map(fn ($row): object => (object) [
                'date' => substr((string) $row->date, 0, 10),
                'type' => (string) $row->type,
                'total' => (int) $row->total,
                'count' => (int) $row->count,
            ]);
    }

    public function findLargest(string $from, string $to, FinanceType $type): ?Finance
    {
        return Finance::query()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->where('type', $type)
            ->orderByDesc('amount')
            ->orderByDesc('id')
            ->first();
    }
}
