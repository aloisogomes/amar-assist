<?php

namespace App\Dto;

final readonly class FinanceDashboardQuery
{
    public function __construct(
        public string $from,
        public string $to,
    ) {}
}
