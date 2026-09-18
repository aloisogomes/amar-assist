<?php

namespace App\Dto;

use App\Enums\FinanceType;

final readonly class CreateFinanceData
{
    public function __construct(
        public string $description,
        public int $amount,
        public string $date,
        public FinanceType $type,
    ) {}

    /**
     * @return array{description: string, amount: int, date: string, type: FinanceType}
     */
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->date,
            'type' => $this->type,
        ];
    }
}
