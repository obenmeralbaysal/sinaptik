<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RoutineController;
use App\Http\Controllers\Api\SocialAccountController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\XmlSourceController;
use App\Http\Controllers\Api\Admin\AdminController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/plans', [PlanController::class, 'public']);

// X OAuth callback (no auth - X redirects user back here)
Route::get('/social-accounts/x/callback', [SocialAccountController::class, 'xCallback']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/switch-organization', [AuthController::class, 'switchOrganization']);

    // Organization-self routes
    Route::middleware('tenant')->group(function () {
        Route::get('/orgs/{orgSlug}', [OrganizationController::class, 'show']);
        Route::patch('/orgs/{orgSlug}', [OrganizationController::class, 'update']);
        Route::get('/orgs/{orgSlug}/members', [OrganizationController::class, 'members']);
        Route::get('/orgs/{orgSlug}/ai-settings', [OrganizationController::class, 'showAiSettings']);
        Route::patch('/orgs/{orgSlug}/ai-settings', [OrganizationController::class, 'updateAiSettings']);
    });

    // Tenant-scoped resources (slug from X-Organization-Slug header)
    Route::middleware('tenant')->group(function () {
        Route::apiResource('social-accounts', SocialAccountController::class)
            ->only(['index', 'show', 'destroy']);
        Route::post('/social-accounts/x/connect', [SocialAccountController::class, 'xConnect']);

        Route::apiResource('targets', TargetController::class);
        Route::post('/targets/{target}/preview', [TargetController::class, 'preview']);

        Route::apiResource('posts', PostController::class);
        Route::post('/posts/{post}/send-now', [PostController::class, 'sendNow']);
        Route::post('/posts/bulk-schedule', [PostController::class, 'bulkSchedule']);

        Route::apiResource('routines', RoutineController::class);
        Route::post('/routines/generate', [RoutineController::class, 'generate']);
        Route::post('/routines/regenerate-one', [RoutineController::class, 'regenerateOne']);
        Route::get('/routines/_meta/models', [RoutineController::class, 'models']);

        Route::apiResource('xml-sources', XmlSourceController::class);
        Route::post('/xml-sources/{xml_source}/run', [XmlSourceController::class, 'runNow']);
    });

    // Admin (super admin only)
    Route::prefix('admin')->middleware('can:access-admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/plans', [AdminController::class, 'plans']);
        Route::post('/plans', [AdminController::class, 'storePlan']);
        Route::patch('/plans/{plan}', [AdminController::class, 'updatePlan']);
        Route::delete('/plans/{plan}', [AdminController::class, 'destroyPlan']);
    });
});
