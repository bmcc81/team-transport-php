<?php
namespace App\Helpers;

class UserAvatar
{
    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials =
            (isset($parts[0][0]) ? $parts[0][0] : '') .
            (isset($parts[1][0]) ? $parts[1][0] : '');

        return strtoupper($initials);
    }
}