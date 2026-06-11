<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Port\DespachoFileStoragePort;

final class DespachoFileStorage implements DespachoFileStoragePort
{
    private const STORAGE_DIR = 'storage/despacho';

    public function __construct(
        private string $projectDir,
    ) {
    }

    public function saveLogo(string $content, string $extension): string
    {
        return $this->saveAsset('logo', $content, $extension);
    }

    public function saveSello(string $content, string $extension): string
    {
        return $this->saveAsset('sello', $content, $extension);
    }

    public function getAbsolutePath(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');

        if (str_starts_with($relativePath, 'var/despacho/')) {
            return $this->projectDir . '/' . $relativePath;
        }

        return $this->projectDir . '/public/' . $relativePath;
    }

    private function saveAsset(string $name, string $content, string $extension): string
    {
        $folder = $this->projectDir . '/public/' . self::STORAGE_DIR . '/';
        $this->ensureWritableDirectory($folder);

        $relativePath = self::STORAGE_DIR . '/' . $name . '.' . $extension;
        $absolutePath = $this->projectDir . '/public/' . $relativePath;

        if (false === file_put_contents($absolutePath, $content)) {
            throw new \RuntimeException('No se pudo guardar la imagen en el servidor.');
        }

        return $relativePath;
    }

    private function ensureWritableDirectory(string $folder): void
    {
        if (is_dir($folder) && is_writable($folder)) {
            return;
        }

        if (!is_dir($folder) && !@mkdir($folder, 0775, true) && !is_dir($folder)) {
            throw new \RuntimeException(
                'No se pudo crear el directorio de almacenamiento. '
                . 'Compruebe permisos de escritura en public/storage/despacho.',
            );
        }

        if (!is_writable($folder)) {
            throw new \RuntimeException(
                'El directorio public/storage/despacho no tiene permisos de escritura para el servidor.',
            );
        }
    }
}
