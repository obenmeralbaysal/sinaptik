<?php

use App\Support\TokenCipher;
use Tests\TestCase;

uses(TestCase::class);

it('decrypts tokens written with the previous key', function () {
    $current = 'base64:'.base64_encode(random_bytes(32));
    $previous = 'base64:'.base64_encode(random_bytes(32));
    config(['sinsocial.token_key' => $previous, 'sinsocial.token_key_previous' => null]);
    $payload = app(TokenCipher::class)->encrypt('page-token');

    config(['sinsocial.token_key' => $current, 'sinsocial.token_key_previous' => $previous]);
    expect(app(TokenCipher::class)->decrypt($payload))->toBe('page-token');
});
