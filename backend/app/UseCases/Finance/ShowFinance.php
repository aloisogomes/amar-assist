<?php

namespace App\UseCases\Finance;

use App\Models\Finance;

class ShowFinance
{
    public function handle(Finance $finance): Finance
    {
        return $finance;
    }
}
