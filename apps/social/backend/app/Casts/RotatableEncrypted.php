<?php

namespace App\Casts;

use App\Support\TokenCipher;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Encrypts secrets with TOKEN_ENCRYPTION_KEY, falling back to previous keys.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class RotatableEncrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return app(TokenCipher::class)->decrypt((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return app(TokenCipher::class)->encrypt((string) $value);
    }
}
