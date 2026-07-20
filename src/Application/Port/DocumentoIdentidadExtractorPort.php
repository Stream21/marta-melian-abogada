<?php

declare(strict_types=1);

namespace App\Application\Port;

interface DocumentoIdentidadExtractorPort
{
    /**
     * @return array{
     *   nombre: string,
     *   nacionalidad: string,
     *   tipoDocumento: string,
     *   numDocumento: string,
     *   fechaNacimiento: string|null,
     *   lugarNacimiento: string,
     *   domicilio: string,
     *   codigoPostal: string,
     *   ciudad: string,
     *   provincia: string,
     *   nombrePadre: string,
     *   nombreMadre: string,
     *   extraccionAutomatica: bool,
     *   camposMrz?: list<string>
     * }
     */
    public function extract(string $tipoEscaneo, string $anversoPath, ?string $reversoPath): array;
}
