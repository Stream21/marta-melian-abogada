<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\ValueObject\ExpedienteId;

interface ExpedienteFileStoragePort
{
    /**
     * Construye la ruta de carpeta para un expediente (ej. var/expedientes/{id}/).
     */
    public function getFolderPath(ExpedienteId $expedienteId): string;

    /**
     * Guarda el contenido binario del PDF en la carpeta del expediente.
     * Retorna la ruta relativa del archivo guardado.
     */
    public function savePdf(ExpedienteId $expedienteId, string $filename, string $content): string;
}
