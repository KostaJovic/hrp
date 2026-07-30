<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\MaintenanceLogController;
use App\Http\Controllers\Api\V1\MaintenancePlanController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => [
        'laravel' => app()->version(),
        'database' => DB::scalar('select version()'),
    ]);

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', fn (Request $request) => $request->user());

        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('items', ItemController::class);
        Route::apiResource('tags', TagController::class);
        Route::apiResource('locations', LocationController::class);
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::apiResource('tasks', TaskController::class);
        Route::apiResource('maintenance-plans', MaintenancePlanController::class);
        Route::apiResource('maintenance-logs', MaintenanceLogController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('budgets', BudgetController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/export/json', [ExportController::class, 'json']);
        Route::get('/export/csv/{entity}', [ExportController::class, 'csv']);
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::apiResource('documents', DocumentController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('projects', ProjectController::class);
    });
});
