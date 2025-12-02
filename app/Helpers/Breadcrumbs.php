<?php
namespace App\Helpers;

class Breadcrumbs
{
    public static function generate(string $uri): array
    {
        $uri = parse_url($uri, PHP_URL_PATH);  // remove query params
        $parts = array_filter(explode('/', trim($uri, '/')));

        $breadcrumbs = [];
        $path = '';

        foreach ($parts as $part) {
            $path .= '/' . $part;

            $breadcrumbs[] = [
                'label' => self::formatLabel($part),
                'url'   => $path,
            ];
        }

        return $breadcrumbs;
    }

    private static function formatLabel(string $segment): string
    {
        // Replace dashes with spaces and uppercase first letters
        return ucwords(str_replace('-', ' ', $segment));
    }
}
