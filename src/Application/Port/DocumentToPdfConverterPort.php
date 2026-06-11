<?php

declare(strict_types=1);

namespace App\Application\Port;

interface DocumentToPdfConverterPort
{
    /**
     * Convierte un archivo subido al formato PDF exigido por las plataformas.
     * Si el origen ya es PDF, devuelve una copia sin transformación.
     *
     * @return string Ruta relativa al PDF generado
     */
    public function convertToPdf(string $sourceAbsolutePath, string $mimeType): string;
}
