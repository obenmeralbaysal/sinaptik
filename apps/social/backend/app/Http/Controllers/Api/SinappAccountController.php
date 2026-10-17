<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Billing\SinappAccountLookup;
use App\Services\Billing\SinappSignature;
use Illuminate\Http\Request;

class SinappAccountController extends Controller
{
    public function __invoke(Request $request, SinappSignature $signature, SinappAccountLookup $lookup)
    {
        $signature->assertLookup($request);
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        return response()->json($lookup->find($data['email']));
    }
}
