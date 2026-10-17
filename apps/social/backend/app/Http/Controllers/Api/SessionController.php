<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $current = $request->session()->getId();
        $rows = DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get();

        return response()->json([
            'sessions' => $rows->map(fn ($row) => [
                'id' => $row->id,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'last_activity' => $row->last_activity,
                'current' => $row->id === $current,
            ]),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        if ($id === $request->session()->getId()) {
            abort(422, 'Geçerli oturum bu listeden kapatılamaz.');
        }
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }
}
