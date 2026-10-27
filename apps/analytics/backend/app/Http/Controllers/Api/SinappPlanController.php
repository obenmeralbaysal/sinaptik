<?php

namespace App\Http\Controllers\Api;

use App\Billing\SinappPlanCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SinappPlanController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()
            ->json(SinappPlanCatalog::payload())
            ->header('Cache-Control', 'public, max-age=60');
    }
}
