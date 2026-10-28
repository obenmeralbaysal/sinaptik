<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;

it('registers, verifies email, resets password, and lists sessions', function () {
    Notification::fake();

    $this->postJson('/api/register', [
        'name' => 'Ada',
        'email' => 'ada@sinapp.test',
        'password' => 'password',
        'workspace_name' => 'Ada',
    ])->assertCreated()->assertJsonPath('user.email', 'ada@sinapp.test');

    $user = User::query()->where('email', 'ada@sinapp.test')->firstOrFail();
    expect($user->email_verified_at)->toBeNull();

    $url = null;
    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class, function ($notification) use ($user, &$url) {
        $url = $notification->toMail($user)->actionUrl;

        return true;
    });

    $this->get($url)->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->postJson('/api/logout')->assertOk();
    $this->postJson('/api/login', ['email' => 'ada@sinapp.test', 'password' => 'password'])->assertOk();
    $this->getJson('/api/sessions')->assertOk()->assertJsonPath('sessions.0.current', true);

    $this->postJson('/api/forgot-password', ['email' => 'ada@sinapp.test'])->assertOk();
    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->postJson('/api/reset-password', [
        'email' => 'ada@sinapp.test',
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    $this->postJson('/api/login', ['email' => 'ada@sinapp.test', 'password' => 'new-password'])->assertOk();
});

it('requires a totp challenge when two factor is enabled', function () {
    $user = User::factory()->create(['password' => 'password']);
    $secret = (new Google2FA)->generateSecretKey();
    $user->forceFill(['totp_secret' => $secret, 'totp_confirmed_at' => now()])->save();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('two_factor', true);

    $this->getJson('/api/user')->assertUnauthorized();

    $code = (new Google2FA)->getCurrentOtp($secret);
    $this->postJson('/api/two-factor/challenge', ['code' => $code])->assertOk();
    $this->getJson('/api/user')->assertOk();
});
