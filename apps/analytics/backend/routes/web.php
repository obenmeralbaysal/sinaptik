<?php

use App\Http\Controllers\Auth\OidcController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/login', [OidcController::class, 'login'])->name('auth.oidc.login');
Route::get('/auth/callback', [OidcController::class, 'callback'])->name('auth.oidc.callback');
