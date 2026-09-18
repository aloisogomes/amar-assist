<?php

namespace App\Dto;

final readonly class ImportFinanceResult
{
    public function __construct(
        public string $importId,
        public string $message = 'Import queued.',
    ) {}
}
