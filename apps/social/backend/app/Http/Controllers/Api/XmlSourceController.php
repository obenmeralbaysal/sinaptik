<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\FetchXmlSource;
use App\Models\XmlSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class XmlSourceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => XmlSource::query()->latest()->get()]);
    }

    public function show(XmlSource $xmlSource): JsonResponse
    {
        return response()->json(['data' => $xmlSource]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'social_account_id' => ['nullable', Rule::exists('social_accounts', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url'],
            'selector' => ['required', 'string', 'max:200'],
            'template' => ['required', 'string', 'max:500'],
            'fetch_interval_minutes' => ['sometimes', 'integer', 'min:5'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $source = XmlSource::create($data);
        return response()->json(['data' => $source], 201);
    }

    public function update(Request $request, XmlSource $xmlSource): JsonResponse
    {
        $data = $request->validate([
            'social_account_id' => ['nullable', Rule::exists('social_accounts', 'id')],
            'name' => ['sometimes', 'string', 'max:120'],
            'url' => ['sometimes', 'url'],
            'selector' => ['sometimes', 'string', 'max:200'],
            'template' => ['sometimes', 'string', 'max:500'],
            'fetch_interval_minutes' => ['sometimes', 'integer', 'min:5'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $xmlSource->update($data);
        return response()->json(['data' => $xmlSource]);
    }

    public function destroy(XmlSource $xmlSource): JsonResponse
    {
        $xmlSource->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function runNow(XmlSource $xmlSource): JsonResponse
    {
        FetchXmlSource::dispatchSync($xmlSource->id);
        return response()->json(['message' => 'Triggered.', 'data' => $xmlSource->fresh()]);
    }
}
