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

    /**
     * Convierte cada archivo a PDF y los combina en un único documento.
     *
     * @param list<array{path: string, mime: string}> $sources Rutas absolutas y MIME de cada archivo
     *
     * @return string Ruta relativa al PDF generado
     */
    public function mergeSourcesToPdf(array $sources): string;
}
