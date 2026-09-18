<?php

namespace App\UseCases\Finance;

use App\Dto\UpdateFinanceData;
use App\Models\Finance;
use App\Repositories\Contracts\FinanceRepositoryInterface;
use App\Support\FinanceDashboardCache;

class UpdateFinance
{
    public function __construct(
        private readonly FinanceRepositoryInterface $finances,
        private readonly FinanceDashboardCache $dashboardCache,
    ) {}

    public function handle(Finance $finance, UpdateFinanceData $data): Finance
    {
        $finance = $this->finances->update($finance, $data);
        $this->dashboardCache->bump();

        return $finance;
    }
}
