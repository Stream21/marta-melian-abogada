<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

final class UploadedArchivosExtractor
{
    /**
     * @return list<array{content: string, mime: string, nombreOriginal?: string}>
     */
    public function extract(Request $request, string $multipleKey = 'archivos', string $singleKey = 'archivo'): array
    {
        $archivos = [];

        $multiples = $request->files->all($multipleKey);
        if ([] === $multiples) {
            $candidato = $request->files->get($multipleKey);
            if (is_array($candidato)) {
                $multiples = $candidato;
            } elseif (null !== $candidato) {
                $multiples = [$candidato];
            }
        }

        foreach ($multiples as $file) {
            $parsed = $this->parseFile($file);
            if (null !== $parsed) {
                $archivos[] = $parsed;
            }
        }

        if ([] === $archivos) {
            $single = $request->files->get($singleKey) ?? $request->files->get('file');
            $parsed = $this->parseFile($single);
            if (null !== $parsed) {
                $archivos[] = $parsed;
            }
        }

        return $archivos;
    }

    /**
     * @return array{content: string, mime: string}|null
     */
    private function parseFile(mixed $file): ?array
    {
        if (!is_object($file) || !method_exists($file, 'getContent')) {
            return null;
        }

        return [
            'content' => (string) $file->getContent(),
            'mime' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
            'nombreOriginal' => method_exists($file, 'getClientOriginalName')
                ? (string) $file->getClientOriginalName()
                : 'documento',
        ];
    }
}
