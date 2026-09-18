<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function financeAuthToken(?User $user = null): string
{
    $user ??= User::factory()->create();

    return $user->createToken('auth')->plainTextToken;
}

function financeUpload(string $filename): UploadedFile
{
    $path = base_path('tests/Fixtures/finances/'.$filename);

    return new UploadedFile($path, $filename, 'text/csv', null, true);
}
