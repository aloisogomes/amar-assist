<?php

use App\Enums\FinanceType;
use App\Models\Finance;
use App\Support\FinanceDashboardCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

describe('dashboard', function () {
    it('returns 401 when no token is provided', function () {
        $this->getJson('/api/finances/dashboard')->assertUnauthorized();
    });

    it('defaults to the current month through today', function () {
        $this->travelTo('2026-09-18');

        Finance::factory()->income()->create([
            'description' => 'Mensalidade',
            'amount' => 100000,
            'date' => '2026-09-05',
        ]);
        Finance::factory()->expense()->create([
            'description' => 'Aluguel',
            'amount' => 40000,
            'date' => '2026-09-10',
        ]);
        Finance::factory()->income()->create([
            'amount' => 5000,
            'date' => '2026-08-20',
        ]);

        $this->withToken(financeAuthToken())
            ->getJson('/api/finances/dashboard')
            ->assertOk()
            ->assertJsonPath('data.from', '2026-09-01')
            ->assertJsonPath('data.to', '2026-09-18')
            ->assertJsonPath('data.kpis.income_total', 100000)
            ->assertJsonPath('data.kpis.expense_total', 40000)
            ->assertJsonPath('data.kpis.balance', 60000)
            ->assertJsonPath('data.kpis.transactions_count', 2)
            ->assertJsonPath('data.kpis.largest_income.description', 'Mensalidade')
            ->assertJsonPath('data.kpis.largest_expense.description', 'Aluguel')
            ->assertJsonCount(18, 'data.series');
    });

    it('filters by the requested period', function () {
        Finance::factory()->income()->create([
            'amount' => 1500,
            'date' => '2026-05-01',
        ]);
        Finance::factory()->expense()->create([
            'amount' => 500,
            'date' => '2026-05-02',
        ]);
        Finance::factory()->income()->create([
            'amount' => 9999,
            'date' => '2026-06-01',
        ]);

        $this->withToken(financeAuthToken())
            ->getJson('/api/finances/dashboard?from=2026-05-01&to=2026-05-02')
            ->assertOk()
            ->assertJsonPath('data.from', '2026-05-01')
            ->assertJsonPath('data.to', '2026-05-02')
            ->assertJsonPath('data.kpis.income_total', 1500)
            ->assertJsonPath('data.kpis.expense_total', 500)
            ->assertJsonPath('data.series.0.income', 1500)
            ->assertJsonPath('data.series.1.expense', 500)
            ->assertJsonCount(2, 'data.series');
    });

    it('returns 422 when from is after to', function () {
        $this->withToken(financeAuthToken())
            ->getJson('/api/finances/dashboard?from=2026-09-10&to=2026-09-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    });

    it('caches the aggregate for the same period', function () {
        Finance::factory()->income()->create([
            'amount' => 2000,
            'date' => '2026-09-02',
        ]);

        $token = financeAuthToken();
        $groupByQueries = 0;

        DB::listen(function ($query) use (&$groupByQueries): void {
            if (str_contains(strtolower($query->sql), 'group by')) {
                $groupByQueries++;
            }
        });

        $this->withToken($token)
            ->getJson('/api/finances/dashboard?from=2026-09-01&to=2026-09-18')
            ->assertOk();

        $afterFirst = $groupByQueries;

        $this->withToken($token)
            ->getJson('/api/finances/dashboard?from=2026-09-01&to=2026-09-18')
            ->assertOk();

        expect($groupByQueries)->toBe($afterFirst)
            ->and(Cache::has((new FinanceDashboardCache)->key('2026-09-01', '2026-09-18')))->toBeTrue();
    });

    it('invalidates the dashboard cache after a create', function () {
        Finance::factory()->income()->create([
            'amount' => 1000,
            'date' => '2026-09-02',
        ]);

        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances/dashboard?from=2026-09-01&to=2026-09-18')
            ->assertJsonPath('data.kpis.income_total', 1000);

        $this->withToken($token)
            ->postJson('/api/finances', [
                'description' => 'Nova receita',
                'amount' => 2500,
                'date' => '2026-09-03',
                'type' => FinanceType::Income->value,
            ])
            ->assertCreated();

        $this->withToken($token)
            ->getJson('/api/finances/dashboard?from=2026-09-01&to=2026-09-18')
            ->assertJsonPath('data.kpis.income_total', 3500);
    });

    it('invalidates the dashboard cache after an import', function () {
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances/dashboard?from=2025-11-01&to=2026-08-31')
            ->assertJsonPath('data.kpis.transactions_count', 0);

        $this->withToken($token)
            ->post('/api/finances/import', [
                'file' => financeUpload('valid.csv'),
            ], ['Accept' => 'application/json'])
            ->assertAccepted();

        $this->withToken($token)
            ->getJson('/api/finances/dashboard?from=2025-11-01&to=2026-08-31')
            ->assertJsonPath('data.kpis.transactions_count', 4);
    });
});
