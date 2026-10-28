<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class GuardrailException extends HttpException
{
    public function __construct(public array $reasons)
    {
        parent::__construct(422, 'Reklam koruması değişikliği reddetti: '.implode(', ', $reasons).'.');
    }
}
