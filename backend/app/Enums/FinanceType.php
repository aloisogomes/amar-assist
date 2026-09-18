<?php

namespace App\Enums;

use InvalidArgumentException;

enum FinanceType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public static function fromSpreadsheet(string $value): self
    {
        return match (mb_strtolower(trim($value))) {
            'income', 'receita' => self::Income,
            'expense', 'despesa' => self::Expense,
            default => throw new InvalidArgumentException("Invalid finance type [{$value}]."),
        };
    }
}
