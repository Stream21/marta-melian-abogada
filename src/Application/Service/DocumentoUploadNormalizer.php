<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\DocumentToPdfConverterPort;
use App\Domain\Entity\TipoDocumentoRequerido;

final class DocumentoUploadNormalizer
{
    public function __construct(
        private DocumentToPdfConverterPort $pdfConverter,
        private string $projectDir,
    ) {
    }

    /**
     * @param list<array{content: string, mime: string, nombreOriginal?: string}> $archivos
     *
     * @return list<array{content: string, nombreOriginal: string}>
     */
    public function normalizarArchivosIndividuales(
        array $archivos,
        TipoDocumentoRequerido $tipo,
        int $maxImagenes,
    ): array {
        @set_time_limit(300);
        $this->validarCantidadArchivos($archivos, $tipo, $maxImagenes);

        $temporales = [];
        $result = [];

        try {
            foreach ($archivos as $i => $archivo) {
                $extension = $this->inferExtension($archivo['mime'], $archivo['content']);
                $temp = sys_get_temp_dir() . '/doc-upload-' . bin2hex(random_bytes(8)) . '-' . $i . $extension;
                file_put_contents($temp, $archivo['content']);
                $temporales[] = $temp;

                $relative = $this->pdfConverter->convertToPdf($temp, $archivo['mime']);
                $pdfAbsolute = $this->projectDir . '/' . ltrim($relative, '/');
                $content = (string) file_get_contents($pdfAbsolute);
                if ($pdfAbsolute !== $temp && is_file($pdfAbsolute)) {
                    @unlink($pdfAbsolute);
                }

                $nombreOriginal = trim((string) ($archivo['nombreOriginal'] ?? ''));
                if ('' === $nombreOriginal) {
                    $nombreOriginal = sprintf('archivo-%d.pdf', $i + 1);
                }

                $result[] = [
                    'content' => $content,
                    'nombreOriginal' => $nombreOriginal,
                ];
            }

            return $result;
        } finally {
            foreach ($temporales as $temp) {
                @unlink($temp);
            }
        }
    }

    /**
     * @param list<array{content: string, mime: string}> $archivos
     */
    public function normalizar(
        array $archivos,
        TipoDocumentoRequerido $tipo,
        int $maxImagenes,
    ): string {
        @set_time_limit(300);
        $this->validarCantidadArchivos($archivos, $tipo, $maxImagenes);

        $temporales = [];
        try {
            $sources = [];
            foreach ($archivos as $i => $archivo) {
                $extension = $this->inferExtension($archivo['mime'], $archivo['content']);
                $temp = sys_get_temp_dir() . '/doc-upload-' . bin2hex(random_bytes(8)) . '-' . $i . $extension;
                file_put_contents($temp, $archivo['content']);
                $temporales[] = $temp;
                $sources[] = [
                    'path' => $temp,
                    'mime' => $archivo['mime'],
                ];
            }

            $relative = $this->pdfConverter->mergeSourcesToPdf($sources);

            return (string) file_get_contents($this->projectDir . '/' . ltrim($relative, '/'));
        } finally {
            foreach ($temporales as $temp) {
                @unlink($temp);
            }
        }
    }

    /**
     * @param list<array{content: string, mime: string}> $archivos
     */
    private function validarCantidadArchivos(array $archivos, TipoDocumentoRequerido $tipo, int $maxImagenes): void
    {
        if ([] === $archivos) {
            throw new \InvalidArgumentException('Debe adjuntar al menos un archivo.');
        }

        if (TipoDocumentoRequerido::Individual === $tipo && count($archivos) > 1) {
            throw new \InvalidArgumentException('Este documento solo admite un archivo.');
        }

        if (count($archivos) > $maxImagenes) {
            throw new \InvalidArgumentException(
                sprintf('Puede subir como máximo %d archivo(s) para este requisito.', $maxImagenes),
            );
        }
    }

    private function inferExtension(string $mimeType, string $content): string
    {
        $mimeType = strtolower(trim($mimeType));

        if (str_contains($mimeType, 'pdf') || str_starts_with($content, '%PDF')) {
            return '.pdf';
        }

        return match (true) {
            str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => '.jpg',
            str_contains($mimeType, 'png') => '.png',
            str_contains($mimeType, 'webp') => '.webp',
            str_contains($mimeType, 'gif') => '.gif',
            str_contains($mimeType, 'wordprocessingml'), str_contains($mimeType, 'docx') => '.docx',
            str_contains($mimeType, 'msword') => '.doc',
            str_contains($mimeType, 'opendocument.text') => '.odt',
            str_contains($mimeType, 'rtf') => '.rtf',
            default => '',
        };
    }
}
