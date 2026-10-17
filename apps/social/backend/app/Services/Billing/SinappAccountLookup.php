<?php

namespace App\Services\Billing;

use App\Models\User;
use App\Models\Workspace;

class SinappAccountLookup
{
    /**
     * @return array{exists: bool, account_id: int|null, display_name: string|null}
     */
    public function find(string $email): array
    {
        $user = User::query()->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])->first();
        $workspace = $user ? $this->ownedWorkspace($user) : null;
        if (! $workspace) {
            return ['exists' => false, 'account_id' => null, 'display_name' => null];
        }

        return [
            'exists' => true,
            'account_id' => $workspace->id,
            'display_name' => $workspace->name,
        ];
    }

    private function ownedWorkspace(User $user): ?Workspace
    {
        $owned = Workspace::query()->where('owner_id', $user->id)->orderBy('id')->get();
        if ($owned->isEmpty()) {
            return null;
        }

        if ($user->current_workspace_id) {
            $current = $owned->firstWhere('id', (int) $user->current_workspace_id);
            if ($current) {
                return $current;
            }
        }

        return $owned->first();
    }
}
