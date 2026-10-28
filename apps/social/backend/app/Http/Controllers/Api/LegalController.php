<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;

class LegalController extends Controller
{
    public function show(string $slug)
    {
        $locale = request('locale', 'tr');
        $page = LegalPage::query()->where('slug', $slug)->where('locale', $locale)->first()
            ?? LegalPage::query()->where('slug', $slug)->where('locale', 'tr')->firstOrFail();

        return response()->json(['page' => $page]);
    }
}
