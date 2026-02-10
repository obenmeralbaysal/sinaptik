<?php
session_start();

use App\Controllers\HomeController;
use App\Controllers\DashboardController;
use App\Controllers\AccountController;
use App\Controllers\TargetController;
use App\Controllers\AuthController;
use App\Controllers\PaymentController;
use App\Controllers\RoutineController;
use App\Controllers\PostController;
use App\Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$router = new Router();

$router->add('GET', '/', [HomeController::class, 'index']);
$router->add('GET', '/privacy-policy', [HomeController::class, 'privacyPolicy']);
$router->add('GET', '/terms-of-use', [HomeController::class, 'termsOfUse']);
$router->add('GET', '/dashboard', [DashboardController::class, 'index']);
$router->add('GET', '/accounts', [AccountController::class, 'index']);
$router->add('GET', '/accounts/connect', [AccountController::class, 'connect']);
$router->add('GET', '/accounts/callback', [AccountController::class, 'callback']);
$router->add('POST', '/accounts/update-settings', [AccountController::class, 'updateSettings']);
$router->add('POST', '/accounts/preview-reply', [AccountController::class, 'previewReply']);

$router->add('GET', '/routines', [RoutineController::class, 'index']);
$router->add('GET', '/routines/create', [RoutineController::class, 'create']);
$router->add('GET', '/routines/preview', [RoutineController::class, 'preview']);

// API Endpoints for Routines
$router->add('POST', '/api/routines/generate', [RoutineController::class, 'generate']);
$router->add('POST', '/api/routines/store', [RoutineController::class, 'store']);
$router->add('POST', '/api/routines/regenerate', [RoutineController::class, 'regenerateSingle']);

$router->add('GET', '/targets', [TargetController::class, 'index']);
$router->add('GET', '/posts', [PostController::class, 'index']);
$router->add('GET', '/targets/create', [TargetController::class, 'create']);
$router->add('POST', '/targets', [TargetController::class, 'store']);
$router->add('GET', '/targets/edit/(\d+)', [TargetController::class, 'edit']);
$router->add('POST', '/targets/update/(\d+)', [TargetController::class, 'update']);
$router->add('POST', '/targets/delete/(\d+)', [TargetController::class, 'delete']);
$router->add('POST', '/targets/fetch-tweet', [TargetController::class, 'fetchLastTweet']);
$router->add('POST', '/targets/verify-user', [TargetController::class, 'verifyUser']);
$router->add('GET', '/login', [AuthController::class, 'login']);
$router->add('POST', '/login', [AuthController::class, 'postLogin']);
$router->add('GET', '/register', [AuthController::class, 'register']);
$router->add('POST', '/register', [AuthController::class, 'postRegister']);
$router->add('GET', '/forgot-password', [AuthController::class, 'forgotPassword']);
$router->add('POST', '/forgot-password', [AuthController::class, 'postForgotPassword']);
$router->add('GET', '/logout', [AuthController::class, 'logout']);
$router->add('GET', '/verify-email', [AuthController::class, 'verifyEmail']);

// Pricing & Payments
$router->add('GET', '/pricing', [PaymentController::class, 'index']);
$router->add('GET', '/checkout', [PaymentController::class, 'checkout']);

// Admin Dashboard
$router->add('GET', '/admin', [\App\Controllers\AdminController::class, 'index']);
$router->add('GET', '/admin/plans', [\App\Controllers\AdminController::class, 'plans']);
$router->add('GET', '/admin/users', [\App\Controllers\AdminController::class, 'users']);
$router->add('POST', '/admin/plans/store', [\App\Controllers\AdminController::class, 'storePlan']);
$router->add('GET', '/admin/status', [\App\Controllers\AdminController::class, 'systemStatus']);

$router->handle();
