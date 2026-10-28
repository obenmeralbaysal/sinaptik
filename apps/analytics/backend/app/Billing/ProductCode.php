<?php

namespace App\Billing;

final class ProductCode
{
    public static function configured(): string
    {
        $code = (string) config('billing.sinapp.product_code', config('sinapp.product_code', 'analytics'));

        return self::valid($code) ? $code : 'analytics';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function resolve(array $data, ?string $current): string
    {
        $fallback = $current !== null && self::valid($current) ? $current : self::configured();
        if (! array_key_exists('product_code', $data)) {
            return $fallback;
        }

        $given = trim((string) $data['product_code']);
        if ($given === '') {
            return $fallback;
        }

        return self::valid($given) ? $given : $fallback;
    }

    private static function valid(string $code): bool
    {
        return preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $code) === 1;
    }
}
