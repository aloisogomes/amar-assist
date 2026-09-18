<?php

use App\Enums\FinanceType;
use App\Jobs\ProcessFinanceImport;
use App\Models\Finance;
use Illuminate\Support\Facades\Queue;

describe('index', function () {
    it('returns 401 when no token is provided', function () {
        $this->getJson('/api/finances')->assertUnauthorized();
    });

    it('returns a paginated list of finances', function () {
        Finance::factory()->count(16)->create();
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances?per_page=15')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);
    });

    it('filters by description query', function () {
        Finance::factory()->create(['description' => 'Aluguel escritório']);
        Finance::factory()->create(['description' => 'Mensalidade cliente']);
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances?q=Aluguel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Aluguel escritório');
    });

    it('filters by type', function () {
        Finance::factory()->income()->create(['description' => 'Receita filtrada']);
        Finance::factory()->expense()->create(['description' => 'Despesa filtrada']);
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances?type=income')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'income')
            ->assertJsonPath('data.0.description', 'Receita filtrada');
    });

    it('filters by date range', function () {
        Finance::factory()->create(['description' => 'Dentro', 'date' => '2026-05-02']);
        Finance::factory()->create(['description' => 'Fora', 'date' => '2026-06-01']);
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances?from=2026-05-01&to=2026-05-31')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Dentro');
    });

    it('filters by amount range', function () {
        Finance::factory()->create(['description' => 'Dentro', 'amount' => 15000]);
        Finance::factory()->create(['description' => 'Fora', 'amount' => 50000]);
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances?min_amount=10000&max_amount=20000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Dentro');
    });

    it('combines filters', function () {
        Finance::factory()->expense()->create([
            'description' => 'Aluguel maio',
            'amount' => 15000,
            'date' => '2026-05-10',
        ]);
        Finance::factory()->expense()->create([
            'description' => 'Aluguel junho',
            'amount' => 15000,
            'date' => '2026-06-10',
        ]);
        Finance::factory()->income()->create([
            'description' => 'Aluguel receita',
            'amount' => 15000,
            'date' => '2026-05-10',
        ]);
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances?q=Aluguel&type=expense&from=2026-05-01&to=2026-05-31&min_amount=10000&max_amount=20000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Aluguel maio');
    });

    it('returns 422 when from is after to', function () {
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances?from=2026-05-31&to=2026-05-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    });
});

describe('store', function () {
    it('creates a finance record', function () {
        $token = financeAuthToken();

        $this->withToken($token)
            ->postJson('/api/finances', [
                'description' => 'Mensalidade Cliente B',
                'amount' => 115346,
                'date' => '2026-05-01',
                'type' => 'income',
            ])
            ->assertCreated()
            ->assertJsonPath('data.description', 'Mensalidade Cliente B')
            ->assertJsonPath('data.amount', 115346)
            ->assertJsonPath('data.date', '2026-05-01')
            ->assertJsonPath('data.type', 'income')
            ->assertJsonMissingPath('data.id');

        $this->assertDatabaseHas('finances', [
            'description' => 'Mensalidade Cliente B',
            'amount' => 115346,
            'type' => FinanceType::Income->value,
        ]);
    });

    it('returns 422 when required fields are missing', function () {
        $token = financeAuthToken();

        $this->withToken($token)
            ->postJson('/api/finances', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'description' => 'Informe a descrição.',
                'amount' => 'Informe o valor.',
                'date' => 'Informe a data.',
                'type' => 'Selecione o tipo.',
            ]);
    });
});

describe('show', function () {
    it('returns the finance by uuid', function () {
        $finance = Finance::factory()->income()->create([
            'description' => 'Serviços Prestados',
            'amount' => 471285,
            'date' => '2026-08-21',
        ]);
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances/'.$finance->uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $finance->uuid)
            ->assertJsonPath('data.description', 'Serviços Prestados')
            ->assertJsonPath('data.amount', 471285)
            ->assertJsonPath('data.type', 'income');
    });

    it('returns 404 when the finance does not exist', function () {
        $token = financeAuthToken();

        $this->withToken($token)
            ->getJson('/api/finances/11111111-1111-1111-1111-111111111111')
            ->assertNotFound();
    });
});

describe('update', function () {
    it('updates the finance record', function () {
        $finance = Finance::factory()->expense()->create([
            'description' => 'Old description',
            'amount' => 1000,
        ]);
        $token = financeAuthToken();

        $this->withToken($token)
            ->putJson('/api/finances/'.$finance->uuid, [
                'description' => 'Updated description',
                'amount' => 2500,
                'date' => '2026-01-10',
                'type' => 'income',
            ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.amount', 2500)
            ->assertJsonPath('data.type', 'income');

        $this->assertDatabaseHas('finances', [
            'uuid' => $finance->uuid,
            'description' => 'Updated description',
            'amount' => 2500,
            'type' => FinanceType::Income->value,
        ]);
    });
});

describe('destroy', function () {
    it('soft deletes the finance record', function () {
        $finance = Finance::factory()->create();
        $token = financeAuthToken();

        $this->withToken($token)
            ->deleteJson('/api/finances/'.$finance->uuid)
            ->assertNoContent();

        $this->assertSoftDeleted($finance);
    });
});

describe('template', function () {
    it('downloads an xlsx template', function () {
        $token = financeAuthToken();

        $this->withToken($token)
            ->get('/api/finances/template')
            ->assertOk()
            ->assertDownload('financial_transactions.xlsx');
    });
});

describe('import', function () {
    it('returns 422 when the spreadsheet headings are invalid', function () {
        $token = financeAuthToken();

        $this->withToken($token)
            ->post('/api/finances/import', [
                'file' => financeUpload('invalid-headers.csv'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'file' => 'The spreadsheet must contain the columns: date, description, amount, type.',
            ]);
    });

    it('dispatches a job for a valid spreadsheet', function () {
        Queue::fake([ProcessFinanceImport::class]);
        $token = financeAuthToken();

        $this->withToken($token)
            ->post('/api/finances/import', [
                'file' => financeUpload('valid.csv'),
            ], ['Accept' => 'application/json'])
            ->assertAccepted()
            ->assertJsonPath('message', 'Import queued.')
            ->assertJsonStructure(['message', 'import_id']);

        Queue::assertPushed(ProcessFinanceImport::class, function (ProcessFinanceImport $job): bool {
            return $job->importId !== '';
        });
        $this->assertDatabaseCount('finances', 0);
    });
});
