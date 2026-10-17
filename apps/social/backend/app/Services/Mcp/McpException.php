<?php

namespace App\Services\Mcp;

use RuntimeException;

class McpException extends RuntimeException
{
    public function __construct(string $message, public int $status = 400, public int $codeId = -32000)
    {
        parent::__construct($message);
    }
}
