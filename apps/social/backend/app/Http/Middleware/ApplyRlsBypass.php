<?php

namespace App\Http\Middleware;

use App\Support\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApplyRlsBypass
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = DB::transactionLevel() === 0;
        if ($started) {
            DB::beginTransaction();
        }

        try {
            app(WorkspaceContext::class)->activate(null, true);
            $response = $next($request);
            if ($started) {
                DB::commit();
            }

            return $response;
        } catch (\Throwable $exception) {
            if ($started && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $exception;
        }
    }
}
