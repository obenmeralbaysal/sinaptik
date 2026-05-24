<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function public(): JsonResponse
    {
        return response()->json([
            'data' => Plan::where('is_active', true)->orderBy('price_cents')->get(),
        ]);
    }
}
