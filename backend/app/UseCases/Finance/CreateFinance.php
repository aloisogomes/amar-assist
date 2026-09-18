<?php

namespace App\UseCases\Finance;

use App\Dto\CreateFinanceData;
use App\Models\Finance;
use App\Repositories\Contracts\FinanceRepositoryInterface;
use App\Support\FinanceDashboardCache;

class CreateFinance
{
    public function __construct(
        private readonly FinanceRepositoryInterface $finances,
        private readonly FinanceDashboardCache $dashboardCache,
    ) {}

    public function handle(CreateFinanceData $data): Finance
    {
        $finance = $this->finances->create($data);
        $this->dashboardCache->bump();

        return $finance;
    }
}
