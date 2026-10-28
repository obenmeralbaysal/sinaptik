<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Notifications\InviteMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(private LimitGuard $limits) {}

    public function invite(Workspace $workspace, User $inviter, string $email, string $role, bool $adsWrite, ?int $brandId = null): Invitation
    {
        $this->limits->assert($workspace, 'seats');
        $plain = Str::random(64);
        $invitation = Invitation::query()->create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brandId,
            'email' => mb_strtolower($email),
            'role' => $role,
            'ads_write' => $adsWrite,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays(7),
            'invited_by' => $inviter->id,
        ]);
        $invitation->setAttribute('plain_token', $plain);

        $user = User::query()->where('email', $invitation->email)->first();
        ($user ?? (new User)->forceFill(['email' => $invitation->email, 'name' => $email]))
            ->notify(new InviteMember($invitation, $plain));

        return $invitation;
    }

    public function revoke(Invitation $invitation): void
    {
        $invitation->update(['revoked_at' => now()]);
    }

    public function accept(User $user, string $token): WorkspaceUser
    {
        $invitation = Invitation::query()->where('token_hash', hash('sha256', $token))->first();
        if (! $invitation || ! $invitation->isOpen()) {
            throw ValidationException::withMessages(['token' => 'Davet geçersiz, süresi dolmuş veya iptal edilmiş.']);
        }
        if (mb_strtolower($user->email) !== $invitation->email) {
            throw ValidationException::withMessages(['token' => 'Bu davet başka bir e-posta adresi için.']);
        }

        return DB::transaction(function () use ($user, $invitation) {
            $workspace = Workspace::query()->findOrFail($invitation->workspace_id);
            $this->limits->assert($workspace, 'seats', 0);
            $used = $this->limits->used($workspace, 'seats');
            $limit = $this->limits->effective($workspace, 'seats');
            if ($limit !== null && $used > $limit) {
                throw new \App\Exceptions\LimitExceeded('seats', $limit);
            }

            $membership = WorkspaceUser::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'user_id' => $user->id],
                ['role' => $invitation->role, 'ads_write' => $invitation->ads_write],
            );
            $invitation->update(['accepted_at' => now()]);
            if (! $user->current_workspace_id) {
                $user->forceFill(['current_workspace_id' => $workspace->id])->save();
            }

            return $membership;
        });
    }
}
