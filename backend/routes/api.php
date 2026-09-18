<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'user'])->name('user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('finances/dashboard', [FinanceController::class, 'dashboard'])->name('finances.dashboard');
    Route::get('finances/template', [FinanceController::class, 'template'])->name('finances.template');
    Route::post('finances/import', [FinanceController::class, 'import'])->name('finances.import');
    Route::apiResource('finances', FinanceController::class);
});
