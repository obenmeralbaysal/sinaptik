<?php

namespace App\Http\Resources;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'external_subject_id' => $this->external_subject_id,
            'issuer' => $this->issuer,
            'current_workspace' => $this->whenLoaded('currentWorkspace', fn () => $this->workspacePayload($this->currentWorkspace)),
            'workspaces' => $this->whenLoaded('workspaces', fn () => $this->workspaces->map(fn (Workspace $workspace) => $this->workspacePayload($workspace))->values()),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function workspacePayload(?Workspace $workspace): ?array
    {
        if (! $workspace) {
            return null;
        }

        return [
            'id' => $workspace->id,
            'slug' => $workspace->slug,
            'name' => $workspace->name,
            'role' => $workspace->pivot->role ?? null,
        ];
    }
}
