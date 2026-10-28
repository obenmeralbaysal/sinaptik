<?php

namespace App\Identity\Oidc;

final class OidcException extends \RuntimeException
{
    public function __construct(string $message, int $status = 0)
    {
        parent::__construct($message, $status);
    }
}
