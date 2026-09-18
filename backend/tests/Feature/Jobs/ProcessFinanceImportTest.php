<?php

use App\Enums\FinanceType;
use App\Events\FinanceImportCompleted;
use App\Events\FinanceImportProgress;
use App\Jobs\ProcessFinanceImport;
use App\Models\Finance;
use App\Models\User;
use App\Repositories\FinanceRepository;
use App\Support\FinanceDashboardCache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

it('creates valid rows and broadcasts the import result', function () {
    Event::fake([FinanceImportCompleted::class, FinanceImportProgress::class]);
    Storage::fake('local');

    $contents = file_get_contents(base_path('tests/Fixtures/finances/mixed.csv'));
    Storage::disk('local')->put('finance-imports/mixed.csv', $contents);

    $user = User::factory()->create();
    $importId = (string) Str::uuid();

    (new ProcessFinanceImport('finance-imports/mixed.csv', $user->id, $importId))
        ->handle(new FinanceRepository, new FinanceDashboardCache);

    $this->assertDatabaseCount('finances', 1);
    $this->assertDatabaseHas('finances', [
        'description' => 'Valid row',
        'amount' => 1500,
        'type' => FinanceType::Income->value,
    ]);

    Event::assertDispatched(FinanceImportCompleted::class, function (FinanceImportCompleted $event) use ($user, $importId): bool {
        return $event->userId === $user->id
            && $event->importId === $importId
            && $event->created === 1
            && $event->failed >= 1
            && $event->percent === 100;
    });

    expect(Finance::query()->count())->toBe(1)
        ->and(Storage::disk('local')->exists('finance-imports/mixed.csv'))->toBeFalse();
});

it('imports in chunks and broadcasts progress for each chunk', function () {
    Event::fake([FinanceImportCompleted::class, FinanceImportProgress::class]);
    Storage::fake('local');

    $contents = file_get_contents(base_path('tests/Fixtures/finances/chunks.csv'));
    Storage::disk('local')->put('finance-imports/chunks.csv', $contents);

    $user = User::factory()->create();
    $importId = (string) Str::uuid();

    (new ProcessFinanceImport('finance-imports/chunks.csv', $user->id, $importId, 10))
        ->handle(new FinanceRepository, new FinanceDashboardCache);

    $this->assertDatabaseCount('finances', 25);

    Event::assertDispatchedTimes(FinanceImportProgress::class, 4);
    Event::assertDispatched(FinanceImportProgress::class, function (FinanceImportProgress $event) use ($user, $importId): bool {
        return $event->userId === $user->id
            && $event->importId === $importId
            && $event->total === 25;
    });
    Event::assertDispatched(FinanceImportCompleted::class, function (FinanceImportCompleted $event) use ($importId): bool {
        return $event->importId === $importId
            && $event->created === 25
            && $event->failed === 0
            && $event->percent === 100;
    });

    expect(Storage::disk('local')->exists('finance-imports/chunks.csv'))->toBeFalse();
});
