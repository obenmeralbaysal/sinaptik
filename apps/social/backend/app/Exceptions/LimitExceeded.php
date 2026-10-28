<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class LimitExceeded extends HttpException
{
    public function __construct(public string $metric, public ?int $limit)
    {
        parent::__construct(422, "Plan limiti aşıldı: {$metric}.");
    }
}
