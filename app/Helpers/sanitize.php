<?php

if (!function_exists('h')) {
    function h($value): string {
        if ($value === null) return '';
        if (is_array($value) || is_object($value)) return '';
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
