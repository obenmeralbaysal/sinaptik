<?php

use App\Http\Controllers\Api\AdsController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\BillingWebhookController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LegalController;
use App\Http\Controllers\Api\McpController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SinappAccountController;
use App\Http\Controllers\Api\SinappPlanController;
use App\Http\Controllers\Api\SinappSubscriptionWebhookController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/plans', [BillingController::class, 'plans']);
Route::get('/sinapp/plans', SinappPlanController::class);
Route::post('/sinapp/accounts/lookup', SinappAccountController::class);
Route::get('/legal/{slug}', [LegalController::class, 'show']);
Route::get('/meta/data-deletion/{code}', [MetaController::class, 'deletionStatus']);
Route::get('/meta/callback', [MetaController::class, 'callback']);
Route::post('/meta/data-deletion', [MetaController::class, 'dataDeletion']);
Route::post('/meta/deauthorize', [MetaController::class, 'deauthorize']);
Route::post('/webhooks/billing/sinapp', BillingWebhookController::class);
Route::post('/webhooks/sinapp-subscriptions', SinappSubscriptionWebhookController::class);
Route::post('/mcp', McpController::class);
Route::get('/oauth/metadata', [OAuthController::class, 'metadata']);
Route::post('/oauth/register', [OAuthController::class, 'register']);
Route::post('/oauth/token', [OAuthController::class, 'token']);

Route::get('/auth/identity', [AuthController::class, 'identity']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgot']);
Route::post('/reset-password', [AuthController::class, 'reset']);
Route::post('/two-factor/challenge', [TwoFactorController::class, 'challenge']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify')->middleware('signed');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/email/verification-notification', [AuthController::class, 'resend']);
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable']);
    Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm']);
    Route::delete('/two-factor', [TwoFactorController::class, 'disable']);
    Route::get('/oauth/authorize', [OAuthController::class, 'authorizeClient']);

    Route::middleware('verified')->group(function () {
        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::post('/workspaces', [WorkspaceController::class, 'store']);
        Route::post('/invites/accept', [WorkspaceController::class, 'acceptInvite']);

        Route::middleware(['workspace', 'writable', 'owner.2fa'])->group(function () {
            Route::get('/workspace', [WorkspaceController::class, 'show']);
            Route::patch('/workspace', [WorkspaceController::class, 'update']);
            Route::post('/workspace/switch', [WorkspaceController::class, 'switch']);
            Route::post('/workspace/transfer', [WorkspaceController::class, 'transfer']);
            Route::delete('/workspace', [WorkspaceController::class, 'destroy']);
            Route::post('/workspace/cancel-deletion', [WorkspaceController::class, 'cancelDeletion']);
            Route::patch('/workspace/ads-settings', [WorkspaceController::class, 'adsSettings']);
            Route::get('/members', [WorkspaceController::class, 'members']);
            Route::patch('/members/{userId}', [WorkspaceController::class, 'updateMember']);
            Route::delete('/members/{userId}', [WorkspaceController::class, 'removeMember']);
            Route::get('/invites', [WorkspaceController::class, 'invites']);
            Route::post('/invites', [WorkspaceController::class, 'invite']);
            Route::delete('/invites/{invitation}', [WorkspaceController::class, 'revokeInvite']);
            Route::get('/brands', [WorkspaceController::class, 'brands']);
            Route::post('/brands', [WorkspaceController::class, 'storeBrand']);

            Route::get('/billing/subscription', [BillingController::class, 'subscription']);
            Route::post('/billing/checkout', [BillingController::class, 'checkout']);

            Route::get('/meta/consent', [MetaController::class, 'consent']);
            Route::post('/meta/connect', [MetaController::class, 'connect']);
            Route::delete('/meta/connection', [MetaController::class, 'withdraw']);
            Route::post('/meta/system-user', [MetaController::class, 'systemUser']);
            Route::post('/meta/assets', [MetaController::class, 'select']);
            Route::get('/accounts', [MetaController::class, 'accounts']);
            Route::post('/accounts/sync', [MetaController::class, 'sync']);

            Route::get('/posts', [PostController::class, 'index']);
            Route::post('/posts', [PostController::class, 'store']);
            Route::post('/posts/{post}/approve', [PostController::class, 'approve']);
            Route::post('/posts/{post}/reject', [PostController::class, 'reject']);
            Route::get('/media', [PostController::class, 'media']);
            Route::post('/media', [PostController::class, 'upload']);
            Route::get('/insights', [PostController::class, 'insights']);

            Route::get('/ad-accounts', [AdsController::class, 'accounts']);
            Route::get('/campaigns', [AdsController::class, 'campaigns']);
            Route::get('/ads/proposals', [AdsController::class, 'proposals']);
            Route::post('/ads/proposals', [AdsController::class, 'propose']);
            Route::post('/ads/proposals/{proposal}/approve', [AdsController::class, 'approve']);
            Route::post('/ads/proposals/{proposal}/reject', [AdsController::class, 'reject']);
            Route::post('/ads/proposals/{proposal}/apply', [AdsController::class, 'apply']);
            Route::get('/ads/changes', [AdsController::class, 'changes']);
            Route::post('/ads/changes/{change}/revert', [AdsController::class, 'revert']);
            Route::get('/automation-rules', [AdsController::class, 'rules']);
            Route::post('/automation-rules', [AdsController::class, 'storeRule']);

            Route::get('/api-keys', [ApiKeyController::class, 'index']);
            Route::post('/api-keys', [ApiKeyController::class, 'store']);
            Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);
            Route::get('/audit-logs', [ApiKeyController::class, 'audit']);
        });
    });
});

Route::get('/media/{media}/file', [PostController::class, 'publicFile'])->name('media.public');
