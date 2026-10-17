<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mcp\McpException;
use App\Services\Mcp\McpServer;
use Illuminate\Http\Request;

class McpController extends Controller
{
    public function __invoke(Request $request, McpServer $server)
    {
        try {
            $result = $server->handle($request);
        } catch (McpException $exception) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $request->json('id'),
                'error' => ['code' => $exception->codeId, 'message' => $exception->getMessage()],
            ], $exception->status);
        }

        if ($result['body'] === null) {
            return response('', $result['http']);
        }

        return response()->json($result['body'], $result['http']);
    }
}
