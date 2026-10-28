<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $this->workspace($request);

        return response()->json([
            'data' => $workspace->sites()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $workspace = $this->workspace($request);
        $data = $this->validated($request, $workspace);

        $site = $workspace->sites()->create($data);

        return response()->json(['data' => $site], 201);
    }

    public function show(Request $request, Site $site): JsonResponse
    {
        $this->authorizeSite($request, $site);

        return response()->json(['data' => $site]);
    }

    public function update(Request $request, Site $site): JsonResponse
    {
        $workspace = $this->authorizeSite($request, $site);
        $site->update($this->validated($request, $workspace, $site));

        return response()->json(['data' => $site->fresh()]);
    }

    public function destroy(Request $request, Site $site): JsonResponse
    {
        $this->authorizeSite($request, $site);
        $site->delete();

        return response()->json(['message' => 'Site silindi.']);
    }

    private function workspace(Request $request): Workspace
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($workspace instanceof Workspace, 400);

        return $workspace;
    }

    private function authorizeSite(Request $request, Site $site): Workspace
    {
        $workspace = $this->workspace($request);
        abort_unless($site->workspace_id === $workspace->id, 404);

        return $workspace;
    }

    /**
     * @return array{name: string, domain: string, google_property_id: string|null}
     */
    private function validated(Request $request, Workspace $workspace, ?Site $site = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sites', 'domain')->where('workspace_id', $workspace->id)->ignore($site?->id),
            ],
            'google_property_id' => ['nullable', 'string', 'max:120'],
        ]);

        $data['google_property_id'] = $data['google_property_id'] ?? null;

        return $data;
    }
}
