<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Billing\SinappCatalog;
use Illuminate\Http\JsonResponse;

class SinappPlanController extends Controller
{
    public function __invoke(SinappCatalog $catalog): JsonResponse
    {
        return response()
            ->json($catalog->payload())
            ->header('Cache-Control', 'public, max-age=60');
    }
}
