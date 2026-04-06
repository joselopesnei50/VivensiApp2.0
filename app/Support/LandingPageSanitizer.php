<?php

namespace App\Support;

class LandingPageSanitizer
{
    public static function url(?string $url, string $fallback = '#'): string
    {
        $u = trim((string) $url);
        if ($u === '') {
            return $fallback;
        }

        // Allow same-page anchors.
        if (str_starts_with($u, '#')) {
            return $u;
        }

        // Allow relative paths (e.g. /storage/..., /images/...) but block protocol-relative URLs (//evil.com).
        if (str_starts_with($u, '/') && !str_starts_with($u, '//')) {
            return $u;
        }

        // Allow safe schemes.
        if (preg_match('/^(https?:\\/\\/)/i', $u)) {
            return $u;
        }
        if (preg_match('/^(mailto:|tel:)/i', $u)) {
            return $u;
        }

        // Block javascript:, data:, file:, etc.
        return $fallback;
    }

    public static function phoneDigits(?string $phone, string $fallback = '5511000000000'): string
    {
        $digits = preg_replace('/\\D+/', '', (string) $phone) ?? '';
        $digits = trim($digits);
        return $digits !== '' ? $digits : $fallback;
    }

    public static function cssColor(?string $value, string $fallback = '#ffffff'): string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return $fallback;
        }

        // Block obvious injection vectors
        if (str_contains($v, ';') || str_contains($v, '{') || str_contains($v, '}') || stripos($v, 'url(') !== false) {
            return $fallback;
        }

        // Hex, rgb/rgba, hsl/hsla, or named colors.
        if (preg_match('/^(#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})|rgba?\\([^\\)]*\\)|hsla?\\([^\\)]*\\)|[a-zA-Z]+)$/i', $v)) {
            return $v;
        }

        return $fallback;
    }

    public static function cssBg(?string $value, string $fallback = '#f8fafc'): string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return $fallback;
        }

        // Block injection vectors
        if (str_contains($v, ';') || str_contains($v, '{') || str_contains($v, '}') || stripos($v, 'url(') !== false) {
            return $fallback;
        }

        // Allow gradients with no dangerous tokens.
        if (preg_match('/^(linear-gradient|radial-gradient)\\([^;\\{\\}]*\\)$/i', $v)) {
            return $v;
        }

        // Fallback to color parsing.
        return self::cssColor($v, $fallback);
    }

    public static function googleMapsEmbedUrl(?string $url, string $fallback = ''): string
    {
        $u = trim((string) $url);
        if ($u === '') {
            return $fallback;
        }

        // Only allow https Google Maps embed URLs.
        if (!preg_match('/^https:\\/\\//i', $u)) {
            return $fallback;
        }

        // Common safe embed patterns.
        $allowed = [
            'https://www.google.com/maps/embed',
            'https://www.google.com/maps/embed?',
            'https://maps.google.com/maps',
            'https://maps.google.com/maps?',
        ];

        foreach ($allowed as $prefix) {
            if (str_starts_with($u, $prefix)) {
                return $u;
            }
        }

        return $fallback;
    }
}

