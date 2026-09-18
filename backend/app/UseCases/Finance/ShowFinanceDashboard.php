<?php

namespace App\UseCases\Finance;

use App\Dto\FinanceDashboardQuery;
use App\Enums\FinanceType;
use App\Models\Finance;
use App\Repositories\Contracts\FinanceRepositoryInterface;
use App\Support\FinanceDashboardCache;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ShowFinanceDashboard
{
    public function __construct(
        private readonly FinanceRepositoryInterface $finances,
        private readonly FinanceDashboardCache $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(?string $from, ?string $to): array
    {
        $query = $this->resolveQuery($from, $to);

        return $this->cache->remember(
            $query->from,
            $query->to,
            fn (): array => $this->build($query),
        );
    }

    private function resolveQuery(?string $from, ?string $to): FinanceDashboardQuery
    {
        $timezone = config('app.timezone');

        return new FinanceDashboardQuery(
            from: $from ?: now($timezone)->startOfMonth()->toDateString(),
            to: $to ?: now($timezone)->toDateString(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function build(FinanceDashboardQuery $query): array
    {
        $from = CarbonImmutable::parse($query->from)->startOfDay();
        $to = CarbonImmutable::parse($query->to)->startOfDay();
        $days = $from->diffInDays($to) + 1;

        $rows = $this->finances->summarizeByPeriod($query->from, $query->to);
        $totals = $this->totalsFromRows($rows);
        $series = $this->series($from, $to, $rows);

        $previousTo = $from->subDay();
        $previousFrom = $previousTo->subDays($days - 1);
        $previousRows = $this->finances->summarizeByPeriod(
            $previousFrom->toDateString(),
            $previousTo->toDateString(),
        );
        $previousTotals = $this->totalsFromRows($previousRows);

        return [
            'from' => $query->from,
            'to' => $query->to,
            'series' => $series,
            'kpis' => [
                'income_total' => $totals['income_total'],
                'expense_total' => $totals['expense_total'],
                'balance' => $totals['income_total'] - $totals['expense_total'],
                'transactions_count' => $totals['transactions_count'],
                'avg_daily_expense' => $days > 0
                    ? (int) round($totals['expense_total'] / $days)
                    : 0,
                'largest_income' => $this->largestPayload(
                    $this->finances->findLargest($query->from, $query->to, FinanceType::Income),
                ),
                'largest_expense' => $this->largestPayload(
                    $this->finances->findLargest($query->from, $query->to, FinanceType::Expense),
                ),
                'previous' => [
                    'from' => $previousFrom->toDateString(),
                    'to' => $previousTo->toDateString(),
                    'income_total' => $previousTotals['income_total'],
                    'expense_total' => $previousTotals['expense_total'],
                    'balance' => $previousTotals['income_total'] - $previousTotals['expense_total'],
                    'income_change' => $this->percentChange($totals['income_total'], $previousTotals['income_total']),
                    'expense_change' => $this->percentChange($totals['expense_total'], $previousTotals['expense_total']),
                    'balance_change' => $this->percentChange(
                        $totals['income_total'] - $totals['expense_total'],
                        $previousTotals['income_total'] - $previousTotals['expense_total'],
                    ),
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, object{date: string, type: string, total: int, count: int}>  $rows
     * @return array{income_total: int, expense_total: int, transactions_count: int}
     */
    private function totalsFromRows($rows): array
    {
        $income = 0;
        $expense = 0;
        $count = 0;

        foreach ($rows as $row) {
            $count += $row->count;

            if ($row->type === FinanceType::Income->value) {
                $income += $row->total;

                continue;
            }

            $expense += $row->total;
        }

        return [
            'income_total' => $income,
            'expense_total' => $expense,
            'transactions_count' => $count,
        ];
    }

    /**
     * @param  Collection<int, object{date: string, type: string, total: int, count: int}>  $rows
     * @return list<array{date: string, income: int, expense: int}>
     */
    private function series(CarbonImmutable $from, CarbonImmutable $to, $rows): array
    {
        $byDate = [];

        foreach ($rows as $row) {
            $byDate[$row->date][$row->type] = $row->total;
        }

        $series = [];
        $cursor = $from;

        while ($cursor->lte($to)) {
            $date = $cursor->toDateString();
            $series[] = [
                'date' => $date,
                'income' => (int) ($byDate[$date][FinanceType::Income->value] ?? 0),
                'expense' => (int) ($byDate[$date][FinanceType::Expense->value] ?? 0),
            ];
            $cursor = $cursor->addDay();
        }

        return $series;
    }

    /**
     * @return array{description: string, amount: int, date: string}|null
     */
    private function largestPayload(?Finance $finance): ?array
    {
        if ($finance === null) {
            return null;
        }

        return [
            'description' => $finance->description,
            'amount' => (int) $finance->amount,
            'date' => $finance->date?->toDateString() ?? '',
        ];
    }

    private function percentChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }
}
