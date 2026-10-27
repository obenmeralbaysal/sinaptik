<?php

namespace App\Identity;

use App\Identity\Oidc\OidcSession;

final class MfaAssurance
{
    /**
     * Local sessions satisfy the privileged-role guard only when the flag is on.
     * The OIDC driver ignores that flag and reads the id_token amr claim.
     */
    public static function satisfied(): bool
    {
        if (config('identity.driver') === 'local' && config('identity.local_mfa') === true) {
            return true;
        }

        $amr = session(OidcSession::AMR, []);

        return is_array($amr) && self::amrSatisfies($amr);
    }

    /**
     * @param  list<mixed>  $amr
     */
    public static function amrSatisfies(array $amr): bool
    {
        foreach ($amr as $value) {
            if (! is_string($value)) {
                continue;
            }
            $normalized = strtolower($value);
            if ($normalized === 'mfa' || $normalized === 'otp') {
                return true;
            }
        }

        return false;
    }
}
