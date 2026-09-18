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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceController extends Controller
{
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

    public function show(Finance $finance, ShowFinance $showFinance): FinanceResource
    {
        return FinanceResource::make($showFinance->handle($finance));
    }

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

    public function destroy(Finance $finance, DeleteFinance $deleteFinance): Response
    {
        $deleteFinance->handle($finance);

        return response()->noContent();
    }

    public function dashboard(FinanceDashboardRequest $request, ShowFinanceDashboard $showFinanceDashboard): JsonResponse
    {
        $dashboard = $showFinanceDashboard->handle(
            $request->validated('from'),
            $request->validated('to'),
        );

        return response()->json(['data' => $dashboard]);
    }

    public function template(DownloadFinanceTemplate $downloadFinanceTemplate): BinaryFileResponse
    {
        return $downloadFinanceTemplate->handle();
    }

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
