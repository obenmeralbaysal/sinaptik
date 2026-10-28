<?php

use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\FrontendRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', FrontendRedirectController::class);

Route::get('/.well-known/oauth-authorization-server', [OAuthController::class, 'metadata']);
