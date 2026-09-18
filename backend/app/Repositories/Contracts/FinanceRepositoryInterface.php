<?php

namespace App\Repositories\Contracts;

use App\Dto\CreateFinanceData;
use App\Dto\ListFinancesQuery;
use App\Dto\UpdateFinanceData;
use App\Enums\FinanceType;
use App\Models\Finance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FinanceRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Finance>
     */
    public function paginate(ListFinancesQuery $query): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?Finance;

    public function create(CreateFinanceData $data): Finance;

    /**
     * @param  list<CreateFinanceData>  $rows
     */
    public function createMany(array $rows): int;

    public function update(Finance $finance, UpdateFinanceData $data): Finance;

    public function delete(Finance $finance): void;

    /**
     * @return Collection<int, object{date: string, type: string, total: int, count: int}>
     */
    public function summarizeByPeriod(string $from, string $to): Collection;

    public function findLargest(string $from, string $to, FinanceType $type): ?Finance;
}
