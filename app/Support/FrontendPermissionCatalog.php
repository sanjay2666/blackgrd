<?php

namespace App\Support;

final class FrontendPermissionCatalog
{
    /** Admin-only permissions never appear in Frontend User customization. */
    public static function keys(): array
    {
        return PermissionRegistry::frontendAssignable();
    }
}
