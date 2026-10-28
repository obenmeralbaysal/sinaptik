<?php

namespace App\Support;

final class Role
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const EDITOR = 'editor';

    public const APPROVER = 'approver';

    public const VIEWER = 'viewer';

    public const ALL = [self::OWNER, self::ADMIN, self::EDITOR, self::APPROVER, self::VIEWER];

    public static function canManageTeam(string $role): bool
    {
        return in_array($role, [self::OWNER, self::ADMIN], true);
    }

    public static function canEditContent(string $role): bool
    {
        return in_array($role, [self::OWNER, self::ADMIN, self::EDITOR], true);
    }

    public static function canApprove(string $role): bool
    {
        return in_array($role, [self::OWNER, self::ADMIN, self::APPROVER], true);
    }

    public static function canWriteAds(string $role, bool $flag): bool
    {
        return in_array($role, [self::OWNER, self::ADMIN], true) || $flag;
    }
}
