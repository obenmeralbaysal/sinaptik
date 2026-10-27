<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\SinappPlanController;
use App\Http\Controllers\Api\SinappSubscriptionWebhookController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/sinapp/plans', SinappPlanController::class);
Route::post('/webhooks/sinapp-subscriptions', SinappSubscriptionWebhookController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/switch-workspace', [AuthController::class, 'switchWorkspace']);
    Route::get('/workspaces', [WorkspaceController::class, 'index']);

    Route::middleware(['workspace', 'mfa.privileged'])->group(function () {
        Route::post('/checkout', [CheckoutController::class, 'store']);

        Route::middleware('subscription')->group(function () {
            Route::get('/sites', [SiteController::class, 'index']);
            Route::post('/sites', [SiteController::class, 'store'])->middleware('limit.sites');
            Route::get('/sites/{site}', [SiteController::class, 'show']);
            Route::patch('/sites/{site}', [SiteController::class, 'update']);
            Route::delete('/sites/{site}', [SiteController::class, 'destroy']);
        });
    });
});
