<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

use App\Application\Port\DocumentToPdfConverterPort;

/**
 * Convierte imágenes a PDF y deja pasar PDFs existentes.
 * Requiere extensión Imagick en el servidor.
 */
final class ImagickDocumentToPdfConverter implements DocumentToPdfConverterPort
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function convertToPdf(string $sourceAbsolutePath, string $mimeType): string
    {
        if (!is_file($sourceAbsolutePath)) {
            throw new \InvalidArgumentException('Archivo de origen no encontrado.');
        }

        $mimeType = strtolower(trim($mimeType));

        if ('application/pdf' === $mimeType) {
            return $this->storeCopy($sourceAbsolutePath, 'pdf');
        }

        if (!extension_loaded('imagick')) {
            throw new \RuntimeException(
                'La conversión a PDF requiere Imagick. Suba un PDF o configure Imagick en el servidor.',
            );
        }

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new \InvalidArgumentException(
                sprintf('Tipo de archivo no convertible a PDF: %s', $mimeType),
            );
        }

        $imagick = new \Imagick();
        $imagick->readImage($sourceAbsolutePath);
        $imagick->setImageFormat('pdf');

        $folder = $this->projectDir . '/var/documentos/convertidos/';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.pdf';
        $absolute = $folder . $filename;
        $imagick->writeImages($absolute, true);
        $imagick->clear();
        $imagick->destroy();

        return 'var/documentos/convertidos/' . $filename;
    }

    private function storeCopy(string $sourceAbsolutePath, string $extension): string
    {
        $folder = $this->projectDir . '/var/documentos/convertidos/';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolute = $folder . $filename;
        copy($sourceAbsolutePath, $absolute);

        return 'var/documentos/convertidos/' . $filename;
    }
}
