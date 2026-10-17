<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

class TokenCipher
{
    public function encrypt(string $value): string
    {
        return $this->encrypter($this->primaryKey())->encryptString($value);
    }

    public function decrypt(string $payload): string
    {
        $last = null;

        foreach ($this->keys() as $key) {
            try {
                return $this->encrypter($key)->decryptString($payload);
            } catch (DecryptException $exception) {
                $last = $exception;
            }
        }

        throw $last ?? new RuntimeException('Token could not be decrypted.');
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_values(array_unique(array_filter([
            config('sinsocial.token_key'),
            config('sinsocial.token_key_previous'),
            config('app.key'),
        ])));
    }

    private function primaryKey(): string
    {
        return $this->keys()[0] ?? throw new RuntimeException('No encryption key configured.');
    }

    private function encrypter(string $key): Encrypter
    {
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            $key = $decoded === false ? $key : $decoded;
        }

        return new Encrypter($key, 'AES-256-CBC');
    }
}
