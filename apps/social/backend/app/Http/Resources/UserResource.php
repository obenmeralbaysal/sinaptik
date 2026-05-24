<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_super_admin' => (bool) $this->is_super_admin,
            'email_verified_at' => $this->email_verified_at,
            'current_organization' => $this->whenLoaded('currentOrganization', function () {
                return $this->currentOrganization ? [
                    'id' => $this->currentOrganization->id,
                    'slug' => $this->currentOrganization->slug,
                    'name' => $this->currentOrganization->name,
                ] : null;
            }),
            'organizations' => $this->whenLoaded('organizations', function () {
                return $this->organizations->map(fn ($o) => [
                    'id' => $o->id,
                    'slug' => $o->slug,
                    'name' => $o->name,
                    'role' => $o->pivot->role ?? null,
                ]);
            }),
        ];
    }
}
