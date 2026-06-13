<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Domain\ValueObject\ExpedienteId;

/**
 * Almacenamiento de archivos (PDFs) por expediente.
 * La ruta base es var/expedientes/{id}/; se crea el directorio si no existe.
 */
final class ExpedienteFileStorage implements ExpedienteFileStoragePort
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function getFolderPath(ExpedienteId $expedienteId): string
    {
        return $this->projectDir . '/var/expedientes/' . $expedienteId->value() . '/';
    }

    public function savePdf(ExpedienteId $expedienteId, string $filename, string $content): string
    {
        $folder = $this->getFolderPath($expedienteId);
        $path = $folder . $filename;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);

        return 'var/expedientes/' . $expedienteId->value() . '/' . $filename;
    }

    public function readRelativePath(string $relativePath): string
    {
        $absolute = $this->projectDir . '/' . ltrim($relativePath, '/');
        if (!is_file($absolute)) {
            throw new \InvalidArgumentException('Archivo no encontrado.');
        }

        return (string) file_get_contents($absolute);
    }

    public function getAbsolutePath(string $relativePath): string
    {
        return $this->projectDir . '/' . ltrim($relativePath, '/');
    }
}
