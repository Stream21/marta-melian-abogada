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
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $path = $folder . $filename;
        file_put_contents($path, $content);

        return 'var/expedientes/' . $expedienteId->value() . '/' . $filename;
    }
}
