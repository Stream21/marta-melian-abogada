<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadedFileMimeDetector
{
    private const EXTENSION_MAP = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    public function detect(UploadedFile $file): string
    {
        $content = (string) file_get_contents($file->getPathname());

        return $this->detectFromContent($content, $file->getClientOriginalExtension());
    }

    public function detectFromPath(string $path): string
    {
        if (!is_file($path)) {
            return 'application/octet-stream';
        }

        return $this->detectFromContent(
            (string) file_get_contents($path),
            pathinfo($path, PATHINFO_EXTENSION),
        );
    }

    public function detectFromContent(string $content, ?string $extension = null): string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(\FILEINFO_MIME_TYPE);
            $detected = $finfo->buffer($content);
            if (is_string($detected) && '' !== $detected && 'application/octet-stream' !== $detected) {
                return $this->normalizeMime($detected);
            }
        }

        if (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($content);
            if (is_array($info) && isset($info['mime']) && is_string($info['mime'])) {
                return $this->normalizeMime($info['mime']);
            }
        }

        if (null !== $extension && '' !== $extension) {
            $ext = strtolower(ltrim($extension, '.'));
            if (isset(self::EXTENSION_MAP[$ext])) {
                return self::EXTENSION_MAP[$ext];
            }
        }

        return 'application/octet-stream';
    }

    private function normalizeMime(string $mime): string
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));

        return match ($mime) {
            'image/jpg' => 'image/jpeg',
            default => $mime,
        };
    }
}
