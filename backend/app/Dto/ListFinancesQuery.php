<?php

namespace App\Dto;

use App\Enums\FinanceType;

final readonly class ListFinancesQuery
{
    public function __construct(
        public int $perPage = 15,
        public ?string $q = null,
        public ?FinanceType $type = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?int $minAmount = null,
        public ?int $maxAmount = null,
    ) {}
}
