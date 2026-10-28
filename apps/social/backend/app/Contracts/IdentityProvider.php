<?php

namespace App\Contracts;

use App\Models\User;

interface IdentityProvider
{
    public function currentUser(): ?User;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function authenticate(array $credentials): User;

    public function logout(): void;
}
