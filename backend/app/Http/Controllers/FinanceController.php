<?php

namespace App\Http\Controllers;

use App\Dto\CreateFinanceData;
use App\Dto\ListFinancesQuery;
use App\Dto\UpdateFinanceData;
use App\Enums\FinanceType;
use App\Http\Requests\Finance\FinanceDashboardRequest;
use App\Http\Requests\Finance\ImportFinanceRequest;
use App\Http\Requests\Finance\ListFinancesRequest;
use App\Http\Requests\Finance\StoreFinanceRequest;
use App\Http\Requests\Finance\UpdateFinanceRequest;
use App\Http\Resources\FinanceResource;
use App\Models\Finance;
use App\UseCases\Finance\CreateFinance;
use App\UseCases\Finance\DeleteFinance;
use App\UseCases\Finance\DownloadFinanceTemplate;
use App\UseCases\Finance\ImportFinances;
use App\UseCases\Finance\ListFinances;
use App\UseCases\Finance\ShowFinance;
use App\UseCases\Finance\ShowFinanceDashboard;
use App\UseCases\Finance\UpdateFinance;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as OpenApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Group('Finanças', weight: 2)]
class FinanceController extends Controller
{
    /**
     * Lista transações paginadas com busca e filtros.
     *
     * Valores em centavos. Filtros: `q`, `type` (`income`|`expense`), `from`, `to`, `min_amount`, `max_amount`, `per_page`.
     */
    public function index(ListFinancesRequest $request, ListFinances $listFinances): AnonymousResourceCollection
    {
        $type = $request->validated('type');

        $finances = $listFinances->handle(new ListFinancesQuery(
            perPage: $request->integer('per_page', 15),
            q: $request->validated('q'),
            type: is_string($type) && $type !== '' ? FinanceType::from($type) : null,
            from: $request->validated('from'),
            to: $request->validated('to'),
            minAmount: $request->filled('min_amount') ? $request->integer('min_amount') : null,
            maxAmount: $request->filled('max_amount') ? $request->integer('max_amount') : null,
        ));

        return FinanceResource::collection($finances);
    }

    /**
     * Cria uma transação.
     *
     * O campo `amount` é o valor em centavos.
     */
    public function store(StoreFinanceRequest $request, CreateFinance $createFinance): JsonResponse
    {
        $finance = $createFinance->handle(new CreateFinanceData(
            description: $request->validated('description'),
            amount: $request->integer('amount'),
            date: $request->validated('date'),
            type: FinanceType::from($request->validated('type')),
        ));

        return FinanceResource::make($finance)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Devolve uma transação pelo UUID.
     */
    public function show(Finance $finance, ShowFinance $showFinance): FinanceResource
    {
        return FinanceResource::make($showFinance->handle($finance));
    }

    /**
     * Atualiza uma transação.
     *
     * O campo `amount` é o valor em centavos.
     */
    public function update(UpdateFinanceRequest $request, Finance $finance, UpdateFinance $updateFinance): FinanceResource
    {
        $finance = $updateFinance->handle($finance, new UpdateFinanceData(
            description: $request->validated('description'),
            amount: $request->integer('amount'),
            date: $request->validated('date'),
            type: FinanceType::from($request->validated('type')),
        ));

        return FinanceResource::make($finance);
    }

    /**
     * Remove uma transação.
     */
    public function destroy(Finance $finance, DeleteFinance $deleteFinance): Response
    {
        $deleteFinance->handle($finance);

        return response()->noContent();
    }

    /**
     * Série diária e KPIs do período.
     *
     * O período padrão é o mês corrente até hoje. Valores em centavos.
     *
     * @response array{
     *     data: array{
     *         from: string,
     *         to: string,
     *         series: list<array{date: string, income: int, expense: int}>,
     *         kpis: array{
     *             income_total: int,
     *             expense_total: int,
     *             balance: int,
     *             transactions_count: int,
     *             avg_daily_expense: int,
     *             largest_income: array{description: string, amount: int, date: string}|null,
     *             largest_expense: array{description: string, amount: int, date: string}|null,
     *             previous: array{
     *                 from: string,
     *                 to: string,
     *                 income_total: int,
     *                 expense_total: int,
     *                 balance: int,
     *                 income_change: float,
     *                 expense_change: float,
     *                 balance_change: float
     *             }
     *         }
     *     }
     * }
     */
    public function dashboard(FinanceDashboardRequest $request, ShowFinanceDashboard $showFinanceDashboard): JsonResponse
    {
        $dashboard = $showFinanceDashboard->handle(
            $request->validated('from'),
            $request->validated('to'),
        );

        return response()->json(['data' => $dashboard]);
    }

    /**
     * Download do modelo de planilha para importação.
     */
    public function template(DownloadFinanceTemplate $downloadFinanceTemplate): BinaryFileResponse
    {
        return $downloadFinanceTemplate->handle();
    }

    /**
     * Enfileira a importação de uma planilha.
     *
     * Aceita `.xlsx` ou `.csv`. O progresso é enviado via WebSocket.
     */
    #[OpenApiResponse(202, type: 'array{message: string, import_id: string}')]
    public function import(ImportFinanceRequest $request, ImportFinances $importFinances): JsonResponse
    {
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $importFinances->handle(
            $file,
            (int) $request->user()->id,
        );

        return response()->json([
            'message' => $result->message,
            'import_id' => $result->importId,
        ], Response::HTTP_ACCEPTED);
    }
}
