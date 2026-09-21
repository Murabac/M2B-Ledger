<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AgentSyncController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\SummaryController;
use App\Http\Middleware\AuthenticateAgentToken;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['ok' => true]);
});

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:api-login');

Route::post('/agent/sync', AgentSyncController::class)
    ->middleware([AuthenticateAgentToken::class, 'throttle:api-agent-sync']);

Route::middleware(['auth:sanctum', 'throttle:api-reads'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/status', StatusController::class);
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->whereNumber('id');
    Route::get('/summary', SummaryController::class);
});
