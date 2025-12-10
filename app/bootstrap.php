<?php
declare(strict_types=1);

/**
 * Global view escape helper.
 * Safe in PHP 8+ (handles null) and uses UTF-8 & ENT_QUOTES.
 */
if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
