<?php

use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Auth\OidcController;
use App\Http\Controllers\FrontendRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', FrontendRedirectController::class);

Route::get('/auth/login', [OidcController::class, 'login'])->name('auth.oidc.login');
Route::get('/auth/callback', [OidcController::class, 'callback'])->name('auth.oidc.callback');

Route::get('/.well-known/oauth-authorization-server', [OAuthController::class, 'metadata']);
