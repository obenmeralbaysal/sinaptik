<?php

namespace App\Identity;

use App\Contracts\IdentityProvider;
use App\Models\User;

/**
 * TODO: Shared Sinaptik login (auth.sinaptik.com.tr, OIDC).
 * This class is intentionally empty. Do not add the authorization-code flow here.
 */
class OidcIdentityProvider implements IdentityProvider
{
    public function currentUser(): ?User
    {
        // TODO: resolve the OIDC subject onto the local identity mirror.

        return null;
    }

    public function authenticate(array $credentials): User
    {
        // TODO: redirect to OIDC_ISSUER and persist external_subject_id + issuer.

        throw new \LogicException('OIDC identity driver is not implemented.');
    }

    public function logout(): void
    {
        // TODO: end the local session and the session at auth.sinaptik.com.tr.
    }
}
