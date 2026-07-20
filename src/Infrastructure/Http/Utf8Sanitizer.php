<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

final class Utf8Sanitizer
{
    public static function sanitize(string $value): string
    {
        if ('' === $value) {
            return '';
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return false !== $converted ? $converted : '';
    }

    public static function sanitizeMixed(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::sanitize($value);
        }

        if (!is_array($value)) {
            return $value;
        }

        $sanitized = [];
        foreach ($value as $key => $item) {
            $safeKey = is_string($key) ? self::sanitize($key) : $key;
            $sanitized[$safeKey] = self::sanitizeMixed($item);
        }

        return $sanitized;
    }

    /**
     * @param list<string> $lines
     */
    public static function sanitizeCommandOutput(array $lines, int $maxLength = 240): string
    {
        $text = self::sanitize(implode(' ', $lines));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength) . '…';
        }

        return $text;
    }
}
