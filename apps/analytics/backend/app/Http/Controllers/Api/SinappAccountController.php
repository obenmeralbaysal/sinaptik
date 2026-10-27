<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Billing\SinappAccountLookup;
use App\Services\Billing\SinappSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SinappAccountController extends Controller
{
    public function __invoke(Request $request, SinappSignature $signature, SinappAccountLookup $lookup): JsonResponse
    {
        if ((string) config('billing.sinapp.integration_key') === '' || (string) config('billing.sinapp.secret') === '') {
            return response()->json(['error' => 'temporarily_unavailable'], 503);
        }

        $signature->assertLookup($request);
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        return response()->json($lookup->find($data['email']));
    }
}
