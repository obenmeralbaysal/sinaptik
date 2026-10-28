<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Billing\SinappCatalog;

class SinappPlanController extends Controller
{
    public function __invoke(SinappCatalog $catalog)
    {
        return response()->json($catalog->payload());
    }
}
