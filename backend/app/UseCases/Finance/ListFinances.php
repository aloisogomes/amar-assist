<?php

namespace App\UseCases\Finance;

use App\Dto\ListFinancesQuery;
use App\Models\Finance;
use App\Repositories\Contracts\FinanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListFinances
{
    public function __construct(
        private readonly FinanceRepositoryInterface $finances,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Finance>
     */
    public function handle(ListFinancesQuery $query): LengthAwarePaginator
    {
        return $this->finances->paginate(new ListFinancesQuery(
            perPage: max(1, min($query->perPage, 100)),
            q: $query->q,
            type: $query->type,
            from: $query->from,
            to: $query->to,
            minAmount: $query->minAmount,
            maxAmount: $query->maxAmount,
        ));
    }
}
