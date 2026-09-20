<?php

namespace App\Services;

class DriveLink
{
    public static function preview(?string $url): ?string
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'drive.google.com'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);
        $id = null;
        if (preg_match('~^/file/d/([A-Za-z0-9_-]+)(?:/(?:view|preview|edit))?/?$~D', $path, $matches)) {
            $id = $matches[1];
        } elseif (in_array($path, ['/open', '/uc'], true) && is_string($query['id'] ?? null)
            && preg_match('/^[A-Za-z0-9_-]+$/D', $query['id'])) {
            $id = $query['id'];
        }
        if (! $id) {
            return null;
        }
        $preview = "https://drive.google.com/file/d/{$id}/preview";
        if (isset($query['resourcekey'])) {
            if (! is_string($query['resourcekey']) || ! preg_match('/^[A-Za-z0-9_-]+$/D', $query['resourcekey'])) {
                return null;
            }
            $preview .= '?resourcekey='.rawurlencode($query['resourcekey']);
        }

        return $preview;
    }

    public static function rule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || self::preview($value) === null) {
                $fail('Gunakan link file Google Drive yang valid, bukan link folder.');
            }
        };
    }
}
