<?php

namespace App\UseCases\Finance;

use App\Models\Finance;
use App\Repositories\Contracts\FinanceRepositoryInterface;
use App\Support\FinanceDashboardCache;

class DeleteFinance
{
    public function __construct(
        private readonly FinanceRepositoryInterface $finances,
        private readonly FinanceDashboardCache $dashboardCache,
    ) {}

    public function handle(Finance $finance): void
    {
        $this->finances->delete($finance);
        $this->dashboardCache->bump();
    }
}
